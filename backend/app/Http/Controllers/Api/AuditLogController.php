<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant audit trail (SPEC.md §8). Restricted to tenant owners and platform
 * admins — staff don't see the full trail.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->isPlatformAdmin() || $user->role->isTenantOwner(), 403);

        return response()->json([
            'data' => AuditLog::query()->with('user')->latest('id')->limit(200)->get(),
        ]);
    }
}
