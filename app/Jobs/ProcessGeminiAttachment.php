<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Models\MedicalAppointment;
use App\Models\MedicalExam;
use App\Models\WhatsappSession;
use App\Services\FinancialRecordService;
use App\Services\Gemini\GeminiAttachmentService;
use App\Services\Waha\WahaClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class ProcessGeminiAttachment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public int $attachmentId, public ?int $sessionId = null)
    {
        $this->onQueue('default');
    }

    public function handle(
        GeminiAttachmentService $geminiAttachmentService,
        FinancialRecordService $financialRecordService,
        WahaClient $wahaClient
    ): void {
        $attachment = Attachment::find($this->attachmentId);

        if (! $attachment) {
            return;
        }

        $session = $this->sessionId ? WhatsappSession::find($this->sessionId) : null;
        $user = $session?->user ?? $attachment->user;

        if ($user) {
            Auth::setUser($user);

            if (empty($user->current_company_id)) {
                $fallbackCompany = $user->companies()->first();

                if ($fallbackCompany) {
                    $user->switchCompany($fallbackCompany);
                }
            }

            if (! empty($user->current_company_id)) {
                session(['current_company_id' => $user->current_company_id]);
            }
        }

        $parsed = $geminiAttachmentService->analyzeAttachment($attachment);

        if ($parsed['intent'] === 'create_financial_record') {
            if (! $user) {
                if ($session) {
                    $wahaClient->sendTextMessage($session->phone_e164, 'Identificamos um lançamento, mas precisamos que conclua seu cadastro no app para vincular.');
                }

                return;
            }

            $record = $financialRecordService->createRecord($user, [
                'type' => $parsed['type'] ?? 'expense',
                'amount' => $parsed['amount'] ?? 0,
                'currency' => 'BRL',
                'occurred_on' => $parsed['occurred_on'] ?? now(),
                'description' => $parsed['description'] ?? 'Lançamento via anexo WhatsApp',
                'metadata' => [
                    'source' => 'whatsapp_attachment',
                    'attachment_id' => $attachment->id,
                ],
            ]);

            if ($session) {
                $wahaClient->sendTextMessage(
                    $session->phone_e164,
                    sprintf(
                        'Lançamento %s registrado a partir do anexo no valor de R$ %.2f.',
                        $record->type,
                        $record->amount
                    )
                );
            }

            return;
        }

        if ($parsed['intent'] === 'create_appointment') {
            if (! $user) {
                if ($session) {
                    $wahaClient->sendTextMessage($session->phone_e164, 'Identificamos uma consulta, mas precisamos do cadastro no app para vincular.');
                }

                return;
            }

            MedicalAppointment::create([
                'user_id' => $user->id,
                'provider_name' => $parsed['provider_name'] ?? 'Consulta (anexo WhatsApp)',
                'occurred_on' => $parsed['occurred_on'] ?? now(),
                'status' => 'scheduled',
                'notes' => $parsed['description'] ?? 'Consulta registrada a partir de anexo.',
            ]);

            if ($session) {
                $wahaClient->sendTextMessage($session->phone_e164, 'Consulta registrada a partir do anexo e vinculada ao seu perfil.');
            }

            return;
        }

        if ($parsed['intent'] === 'create_exam') {
            if (! $user) {
                if ($session) {
                    $wahaClient->sendTextMessage($session->phone_e164, 'Identificamos um exame, mas precisamos do cadastro no app para vincular.');
                }

                return;
            }

            MedicalExam::create([
                'user_id' => $user->id,
                'exam_type' => $parsed['exam_type'] ?? 'Exame (anexo WhatsApp)',
                'occurred_on' => $parsed['occurred_on'] ?? now(),
                'status' => 'scheduled',
                'notes' => $parsed['description'] ?? 'Exame registrado a partir de anexo.',
            ]);

            if ($session) {
                $wahaClient->sendTextMessage($session->phone_e164, 'Exame registrado a partir do anexo e vinculado ao seu perfil.');
            }

            return;
        }

        if ($session) {
            $wahaClient->sendTextMessage($session->phone_e164, 'Não consegui entender as informações desse anexo. Pode tentar enviar um texto explicando o que é esse lançamento?');
        }
    }
}
