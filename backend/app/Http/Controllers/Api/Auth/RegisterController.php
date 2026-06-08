<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Audit\AuditLogger;
use App\Consents\ConsentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    /**
     * Register a new tenant and its owning user, returning an API token.
     */
    public function store(RegisterRequest $request, ConsentService $consents, AuditLogger $audit): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data, $consents): User {
            $tenant = Tenant::create([
                'name' => $data['tenant_name'],
                'type' => $data['tenant_type'],
            ]);

            $user = $tenant->users()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $tenant->type->ownerRole(),
            ]);

            // Capture lawful-basis / cross-border-transfer consent at onboarding.
            $consents->recordOnboarding($user);

            return $user;
        });

        $audit->log('auth.registered', $user, $user);

        $token = $user->createToken('registration')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ], 201);
    }
}
