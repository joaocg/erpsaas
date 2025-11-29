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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class WahaWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        WebhookLog::create([
            'provider' => 'waha',
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        $message = $this->extractIncomingMessage($payload);
        $phone = $this->extractPhone($message ?? $payload);

        if (! $phone) {
            Log::warning('WAHA webhook without phone', ['payload' => Arr::only($payload, ['event', 'payload.event'])]);

            return response()->json(['message' => 'Número ausente.'], 422);
        }

        $session = WhatsappSession::firstOrCreate(
            ['phone_e164' => $phone],
            ['last_message_at' => now()]
        );

        if (! $session->user_id) {
            $user = User::where('phone_e164', $phone)->first();

            if ($user) {
                $session->user()->associate($user);
                $session->save();
            }
        }

        $mediaUrl = $this->extractMediaUrl($message ?? $payload);

        if ($mediaUrl) {
            $attachment = Attachment::create([
                'user_id' => $session->user_id,
                'path' => $mediaUrl,
                'source' => 'whatsapp',
                'raw_payload' => $payload,
            ]);

            ProcessGeminiAttachment::dispatch($attachment);
        }

        $text = $this->extractText($message ?? $payload);

        if ($text) {
            HandleWhatsappMessage::dispatch($session->id, $text);
        }

        return response()->json(['status' => 'accepted']);
    }

    protected function extractIncomingMessage(array $payload): ?array
    {
        $messages = data_get($payload, 'payload.data.messages');

        if (is_array($messages)) {
            $incoming = collect($messages)->first(function ($message) {
                return data_get($message, 'key.fromMe') === false;
            });

            if ($incoming) {
                return $incoming;
            }
        }

        return data_get($payload, 'messages.0');
    }

    protected function extractPhone(array $message): ?string
    {
        $phone = data_get($message, 'key.remoteJid') ?? data_get($message, 'from');

        if (! $phone) {
            return null;
        }

        return preg_replace('/@.*$/', '', $phone);
    }

    protected function extractMediaUrl(array $message): ?string
    {
        return data_get($message, 'message.imageMessage.url')
            ?? data_get($message, 'message.documentMessage.url')
            ?? data_get($message, 'messages.0.image.url')
            ?? data_get($message, 'messages.0.document.url')
            ?? data_get($message, 'media.url');
    }

    protected function extractText(array $message): ?string
    {
        return data_get($message, 'message.conversation')
            ?? data_get($message, 'message.extendedTextMessage.text')
            ?? data_get($message, 'messages.0.text.body')
            ?? data_get($message, 'message');
    }
}
