<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Audit\AuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Authenticate by email/password and issue an API token.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        // Bypass tenant scoping: at login there is no tenant context yet, and
        // email is globally unique, so this resolves the user unambiguously.
        $user = User::withoutTenancy()->where('email', $credentials['email'])->first();

        if ($user === null || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        app(AuditLogger::class)->log('auth.login', $user, $user);

        $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    /**
     * Revoke the token used for the current request (logout).
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
