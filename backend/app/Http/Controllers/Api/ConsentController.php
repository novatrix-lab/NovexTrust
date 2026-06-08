<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Consent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConsentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Consent::query()->latest('id')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purpose' => ['required', 'string', 'max:255'],
            'transfer_basis' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $request->user()->tenant_id)],
        ]);

        $consent = Consent::create([
            ...$data,
            'user_id' => $data['user_id'] ?? $request->user()->id,
            'granted_at' => now(),
        ]);

        return response()->json(['data' => $consent], 201);
    }
}
