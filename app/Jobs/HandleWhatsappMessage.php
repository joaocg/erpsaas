<?php

namespace App\Jobs;

 use App\Models\FinancialRecord;
 use App\Models\MedicalAppointment;
 use App\Models\MedicalExam;
use App\Models\User;
use App\Models\WhatsappSession;
use App\Services\FinancialRecordService;
use App\Services\IntentDetectionService;
use App\Services\Waha\WahaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;


class HandleWhatsappMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $sessionId, public string $message)
    {
    }

    public function handle(
        IntentDetectionService $intentDetectionService,
        FinancialRecordService $financialRecordService,
        WahaClient $wahaClient
    ): void {
        $session = WhatsappSession::find($this->sessionId);

        if (! $session) {
            return;
        }

        $session->update(['last_message_at' => now()]);

        $user = $session->user ?? User::where('phone_e164', $session->phone_e164)->first();

        if ($user && ! $session->user_id) {
            $session->user()->associate($user);
            $session->save();
        }

        if ($user) {
            Auth::setUser($user);

            if (! empty($user->current_company_id)) {
                session(['current_company_id' => $user->current_company_id]);
            }
        }

        $intent = $intentDetectionService->detect($this->message);
        $session->update(['last_intent' => $intent['intent']]);

        if (! $user) {
            $wahaClient->sendTextMessage($session->phone_e164, 'Precisamos que você conclua seu cadastro. Acesse o app para continuar.');

            return;
        }

        if ($intent['intent'] === 'create_financial_record') {
            $record = $financialRecordService->createRecord($user, [
                'type' => $intent['type'] ?? 'expense',
                'amount' => $intent['amount'] ?? 0,
                'currency' => 'BRL',
                'occurred_on' => now(),
                'description' => $this->message,
                'metadata' => ['source' => 'whatsapp'],
            ]);

            $wahaClient->sendTextMessage($session->phone_e164, sprintf(
                'Lançamento %s registrado no valor de R$ %.2f.',
                $record->type,
                $record->amount
            ));

            return;
        }

        if ($intent['intent'] === 'create_appointment') {
            $appointment = MedicalAppointment::create([
                'user_id' => $user->id,
                'provider_name' => 'Consulta via WhatsApp',
                'occurred_on' => now(),
                'status' => 'scheduled',
                'notes' => $this->message,
            ]);

            $wahaClient->sendTextMessage($session->phone_e164, 'Consulta registrada e vinculada ao seu perfil.');

            return;
        }

        if ($intent['intent'] === 'create_exam') {
            $exam = MedicalExam::create([
                'user_id' => $user->id,
                'exam_type' => 'Exame via WhatsApp',
                'occurred_on' => now(),
                'status' => 'scheduled',
                'notes' => $this->message,
            ]);

            $wahaClient->sendTextMessage($session->phone_e164, 'Exame registrado e vinculado ao seu perfil.');

            return;
        }

        if ($intent['intent'] === 'list_recent') {
            $recent = FinancialRecord::where('user_id', $user->id)
                ->latest('occurred_on')
                ->take(5)
                ->get()
                ->map(fn ($record) => sprintf('%s: R$ %.2f em %s', $record->type, $record->amount, optional($record->occurred_on)->format('d/m')))
                ->implode("\n");

            $wahaClient->sendTextMessage($session->phone_e164, $recent ?: 'Sem lançamentos recentes.');

            return;
        }

        $wahaClient->sendTextMessage($session->phone_e164, 'Não entendi. Envie "despesa", "receita", "consulta" ou "exame" para começar.');
    }
}
