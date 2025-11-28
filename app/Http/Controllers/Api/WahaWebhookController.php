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

        $phone = data_get($payload, 'messages.0.from') ?? data_get($payload, 'from');

        if (! $phone) {
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

        $mediaUrl = data_get($payload, 'messages.0.image.url')
            ?? data_get($payload, 'messages.0.document.url')
            ?? data_get($payload, 'media.url');

        if ($mediaUrl) {
            $attachment = Attachment::create([
                'user_id' => $session->user_id,
                'path' => $mediaUrl,
                'source' => 'whatsapp',
                'raw_payload' => $payload,
            ]);

            ProcessGeminiAttachment::dispatch($attachment);
        }

        $text = data_get($payload, 'messages.0.text.body') ?? data_get($payload, 'message');

        if ($text) {
            HandleWhatsappMessage::dispatch($session->id, $text);
        }

        return response()->json(['status' => 'accepted']);
    }
}
