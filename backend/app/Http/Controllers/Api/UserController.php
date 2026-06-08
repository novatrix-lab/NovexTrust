<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Minimal users endpoint. Its purpose in milestone 2 is to demonstrate — and
 * test — strict per-tenant isolation: every query here is automatically
 * constrained to the caller's tenant by {@see TenantScope}.
 */
class UserController extends Controller
{
    /** The authenticated user. */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function index(): JsonResponse
    {
        // Tenant-scoped automatically (the IdentifyTenant middleware has set the
        // context by the time this runs). A platform admin sees all tenants.
        return response()->json([
            'data' => User::query()->orderBy('id')->get(),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        // Explicit lookup (not implicit route-model binding) so the tenant scope
        // is guaranteed to be in effect — a cross-tenant id yields a 404, never
        // another tenant's record.
        $user = User::query()->findOrFail($id);

        return response()->json(['data' => $user]);
    }
}
