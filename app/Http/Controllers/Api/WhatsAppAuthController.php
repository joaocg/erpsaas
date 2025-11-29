<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Waha\WahaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WhatsAppAuthController extends Controller
{
    public function verify(Request $request, WahaClient $wahaClient)
    {
        $data = Validator::make($request->all(), [
            'phone_e164' => ['required', 'string'],
        ])->validate();

        $code = (string) random_int(100000, 999999);

        Cache::put($this->cacheKey($data['phone_e164']), $code, now()->addMinutes(10));

        $wahaClient->sendTextMessage($data['phone_e164'], "Seu código de verificação é {$code}");

        return response()->json([
            'message' => 'Código enviado via WhatsApp.',
        ]);
    }

    public function confirm(Request $request)
    {
        $data = Validator::make($request->all(), [
            'phone_e164' => ['required', 'string'],
            'code' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ])->validate();

        $cached = Cache::get($this->cacheKey($data['phone_e164']));

        if ($cached !== $data['code']) {
            return response()->json([
                'message' => 'Código inválido ou expirado.',
            ], 422);
        }

        $user = User::firstOrCreate(
            ['phone_e164' => $data['phone_e164']],
            [
                'name' => $data['name'] ?? 'Usuário WhatsApp',
                'email' => $data['email'] ?? Str::uuid().'@example.com',
                'password' => Hash::make($data['password'] ?? Str::random(12)),
            ]
        );

        $user->forceFill([
            'whatsapp_opt_in_at' => now(),
        ])->save();

        Cache::forget($this->cacheKey($data['phone_e164']));

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    private function cacheKey(string $phone): string
    {
        return 'whatsapp:verify:'.$phone;
    }
}
