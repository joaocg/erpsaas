<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\HandleWhatsappMessage;
use App\Jobs\ProcessGeminiAttachment;
use App\Models\Attachment;
use App\Models\User;
use App\Models\WebhookLog;
use App\Models\WhatsappSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class WahaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        /**
         * Log bruto do webhook pra análise futura
         */
        WebhookLog::create([
            'provider' => 'waha',
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        /**
         * Normaliza o webhook da WAHA em algo que a gente consiga usar
         */
        $normalized = $this->extractIncomingMessage($payload);

        if (! $normalized) {
            Log::info('WAHA normalized message: ignorada (sem conteúdo ou mensagem nossa)', [
                'event'   => data_get($payload, 'event'),
                'payload_event' => data_get($payload, 'payload.event'),
            ]);

            return response()->json(['status' => 'ignored']);
        }

        Log::info('WAHA normalized message', $normalized);

        /**
         * Se não for uma mensagem válida (ou é mensagem nossa / status / outro evento), só responde OK
         */
        if (! $normalized) {
            return response()->json(['status' => 'ignored']);
        }

        $phone = $normalized['phone'];      // ex: 558897412992 ou 558897412992
        $text = $normalized['text'];       // texto da mensagem (se tiver)
        $mediaUrl = $normalized['media_url'];  // URL da imagem/doc (se tiver)

        if (! $phone) {
            Log::warning('WAHA Número ausente.', ['payload' => $normalized]);
            return response()->json(['message' => 'Número ausente.'], 422);
        }

        /**
         * Sessão por telefone (usando o chatId da WAHA mesmo, como antes)
         */
        $session = WhatsappSession::firstOrCreate(
            ['phone_e164' => $phone],
            ['last_message_at' => now()]
        );

        /**
         * Vincula usuário, se ainda não tiver
         */
        if (! $session->user_id) {
            $user = User::where('phone_e164', $phone)->first();

            if ($user) {
                $session->user()->associate($user);
                $session->save();
            }
        } else {
            $user = $session->user;
        }

        /**
         * Se tiver usuário, seta contexto de autenticação e empresa
         * para o CurrentCompanyScope funcionar.
         */
        if ($user) {
            Auth::setUser($user);

            if (! empty($user->current_company_id)) {
                session(['current_company_id' => $user->current_company_id]);
            }
        }

        /**
         * Se for mídia (imagem/doc), cria Attachment e joga pro Gemini
         */
        if ($mediaUrl) {
            $storagePath = $this->downloadWhatsappMedia($mediaUrl, $session);

            if ($storagePath) {
                $attachment = Attachment::create([
                    'user_id' => $session->user_id,
                    'path' => $storagePath,
                    'source' => 'whatsapp',
                    'raw_payload' => $payload,
                ]);

                ProcessGeminiAttachment::dispatch($attachment);
            }
        }

        /**
         * Se tiver texto, dispara o fluxo de intenção
         */
        if ($text) {
            HandleWhatsappMessage::dispatch($session->id, $text);
        }

        return response()->json(['status' => 'accepted']);
    }

    /**
     * Normaliza os diferentes formatos de webhook da WAHA
     * e retorna apenas mensagens ENTRANTES do cliente.
     *
     * Retorna:
     * [
     *   'phone'     => string|null,
     *   'text'      => string|null,
     *   'media_url' => string|null,
     * ]
     *
     * ou null se não for algo que devamos processar.
     */
    protected function extractIncomingMessage(array $payload): ?array
    {
        $topEvent = data_get($payload, 'event');           // "message" ou "engine.event"
        $innerEvent = data_get($payload, 'payload.event');   // ex: "messages.upsert"
        $mediaUrl = null;
        $text = null;
        $phone = null;
        $fromMe = null;

        /**
         * FORMATO 1: event = "message"
         */
        if ($topEvent === 'message') {
            $fromMe = data_get($payload, 'payload.fromMe');

            /**
             * Só processa mensagens do cliente (fromMe == false)
             */
            if ($fromMe !== false) {
                return null;
            }

            /**
             * Ignora status (status@broadcast)
             */
            $from = data_get($payload, 'payload.from');
            if ($from === 'status@broadcast') {
                return null;
            }

            /**
             * Telefone do cliente (chatId)
             */
            $phone = $from
                ?? data_get($payload, 'payload._data.key.remoteJid')
                ?? data_get($payload, 'payload.participant');

            /**
             * URL da mídia (imagem/doc)
             */
            $mediaUrl =
                data_get($payload, 'payload.media.url')
                ?? data_get($payload, 'payload._data.message.imageMessage.url')
                ?? data_get($payload, 'payload._data.message.documentMessage.url');

            /**
             * Texto
             */
            $text =
                data_get($payload, 'payload.body')
                ?? data_get($payload, 'payload._data.message.conversation')
                ?? data_get($payload, 'payload._data.message.extendedTextMessage.text');
        }

        /**
         * FORMATO 2: event = "engine.event" + payload.event = "messages.upsert"
         */
        if ($topEvent === 'engine.event' && $innerEvent === 'messages.upsert') {
            $message = data_get($payload, 'payload.data.messages.0');

            if (! $message) {
                return null;
            }

            $fromMe = data_get($message, 'key.fromMe');

            /**
             * Só mensagens do cliente
             */
            if ($fromMe !== false) {
                return null;
            }

            $phone =
                data_get($message, 'key.remoteJid')
                ?? data_get($message, 'key.remoteJidAlt')
                ?? data_get($message, 'key.participant');

            /**
             * Ignora status
             */
            if ($phone === 'status@broadcast') {
                return null;
            }

            $mediaUrl =
                data_get($message, 'message.imageMessage.url')
                ?? data_get($message, 'message.documentMessage.url');

            $text =
                data_get($message, 'message.conversation')
                ?? data_get($message, 'message.extendedTextMessage.text');
        }

        if (! $phone && ! $text && ! $mediaUrl) {
            return null;
        }

        return [
            'phone' => $this->cleanPhone($phone),
            'text' => $text,
            'media_url' => $mediaUrl,
        ];
    }

    protected function cleanPhone(?string $number): ?string
    {
        if (! $number) {
            return null;
        }

        // Remove tudo após @
        $number = explode('@', $number)[0];

        // Remove qualquer sufixo após :
        $number = explode(':', $number)[0];

        // Mantém só números
        return preg_replace('/\D/', '', $number);
    }

    protected function downloadWhatsappMedia(string $mediaUrl, WhatsappSession $session): ?string
    {
        $token = config('services.waha.token');

        if (empty($token)) {
            Log::warning('WAHA token missing, cannot download media.', [
                'media_url' => $mediaUrl,
            ]);

            return null;
        }

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $token,
            ])->timeout(20)->get($mediaUrl);

            if ($response->failed()) {
                Log::warning('WAHA media download failed', [
                    'media_url' => $mediaUrl,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $path = parse_url($mediaUrl, PHP_URL_PATH) ?: $mediaUrl;
            $filename = basename($path) ?: Str::uuid()->toString();

            $storagePath = sprintf('whatsapp/%s/%s', $session->phone_e164, $filename);

            Storage::put($storagePath, $response->body());

            return $storagePath;
        } catch (\Throwable $exception) {
            Log::error('WAHA media download exception', [
                'media_url' => $mediaUrl,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
