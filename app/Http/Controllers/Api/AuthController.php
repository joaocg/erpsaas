<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_e164' => ['nullable', 'string', 'unique:users,phone_e164'],
            'password' => ['required', 'string', 'min:8'],
        ])->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone_e164' => $data['phone_e164'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = Validator::make($request->all(), [
            'email' => ['nullable', 'email'],
            'phone_e164' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ])->validate();

        $user = null;

        if (! empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
        }

        if (! $user && ! empty($data['phone_e164'])) {
            $user = User::where('phone_e164', $data['phone_e164'])->first();
        }

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 422);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
