<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only view of the caller's tenant deadlines — the data source for the
 * agency cockpit dashboard (M9). Tenant-scoped automatically.
 */
class DeadlineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Deadline::query()
            ->with(['document.documentType', 'document.entity', 'document.person'])
            ->orderBy('due_date');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json(['data' => $query->get()]);
    }
}
