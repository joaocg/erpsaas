<?php

namespace App\Jobs;

use App\Models\Attachment;
use App\Models\MedicalAppointment;
use App\Models\MedicalExam;
use App\Services\FinancialRecordService;
use App\Services\Gemini\GeminiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProcessGeminiAttachment implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public Attachment $attachment)
    {
        $this->onQueue('default');
    }

    public function handle(GeminiClient $geminiClient, FinancialRecordService $financialRecordService): void
    {
        $attachment = Attachment::find($this->attachment->id);

        if (! $attachment) {
            return;
        }

        // Se o anexo tiver usuário associado, define o contexto de autenticação/empresa
        if ($attachment->user) {
            $user = $attachment->user;
            Auth::setUser($user);

            if (! empty($user->current_company_id)) {
                session(['current_company_id' => $user->current_company_id]);
            }
        }

        // Constrói o contexto para a Gemini (valor, tipo etc.)
        $context = [
            'type'     => $attachment->gemini_detected_type,
            'amount'   => $attachment->gemini_amount,
            'currency' => $attachment->gemini_currency,
        ];

        $storagePath = $attachment->path;

        // Se o arquivo não estiver no storage (pode ser um caminho externo), envia o caminho original
        if (! Storage::exists($storagePath)) {
            Log::warning('Attachment not found in storage for Gemini processing.', [
                'attachment_id' => $attachment->id,
                'path'          => $storagePath,
            ]);

            $result = $geminiClient->analyze($storagePath, $context);
        } else {
            // Obtém o caminho absoluto e envia para a Gemini
            $localPath = Storage::path($storagePath);
            $result    = $geminiClient->analyze($localPath, $context);
        }

        // Atualiza o modelo Attachment com os resultados da Gemini
        $attachment->update([
            'gemini_status'       => 'processed',
            'gemini_summary'      => $result['summary'] ?? null,
            'gemini_topics'       => $result['topics'] ?? [],
            'gemini_amount'       => $result['amount'] ?? $attachment->gemini_amount,
            'gemini_currency'     => $result['currency'] ?? $attachment->gemini_currency,
            'gemini_detected_type'=> $result['detected_type'] ?? $attachment->gemini_detected_type,
            'processed_at'        => now(),
        ]);

        $detectedType = $attachment->gemini_detected_type ?? $result['detected_type'] ?? null;

        if (! $detectedType || ! $attachment->user) {
            return;
        }

        if (in_array($detectedType, ['expense', 'income'], true)) {
            $financialRecordService->createRecord($attachment->user, [
                'type' => $detectedType,
                'amount' => $result['amount'] ?? 0,
                'currency' => $result['currency'] ?? 'BRL',
                'occurred_on' => now(),
                'attachment_id' => $attachment->id,
                'description' => $result['summary'] ?? 'Lançamento automático',
                'metadata' => $result,
            ]);
        }

        if ($detectedType === 'appointment') {
            MedicalAppointment::create([
                'user_id' => $attachment->user_id,
                'attachment_id' => $attachment->id,
                'provider_name' => 'Consulta Gemini',
                'occurred_on' => now(),
                'status' => 'done',
                'notes' => $result['summary'] ?? null,
                'metadata' => $result,
            ]);
        }

        if ($detectedType === 'exam') {
            MedicalExam::create([
                'user_id' => $attachment->user_id,
                'attachment_id' => $attachment->id,
                'exam_type' => 'Exame Gemini',
                'occurred_on' => now(),
                'status' => 'done',
                'notes' => $result['summary'] ?? null,
                'results_json' => $result,
            ]);
        }
    }
}
