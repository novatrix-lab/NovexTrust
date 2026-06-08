<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Audit\AuditLogger;
use App\Enums\DeadlineStatus;
use App\Models\Deadline;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The agency cockpit dashboard: every tenant deadline with traffic-light status
 * (SPEC.md §8). All queries are tenant-scoped automatically. Actions (mark done,
 * assign) operate only within the tenant — a foreign id 404s via the scope.
 */
#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public string $status = '';

    public function markDone(int $deadlineId): void
    {
        $deadline = Deadline::query()->findOrFail($deadlineId);
        $deadline->update(['status' => DeadlineStatus::Done]);

        app(AuditLogger::class)->log('deadline.completed', $deadline);
    }

    public function assign(int $deadlineId, ?int $userId): void
    {
        // Validate the user is in the tenant (scope makes find() tenant-bound).
        $resolved = $userId !== null ? User::query()->find($userId)?->id : null;

        $deadline = Deadline::query()->findOrFail($deadlineId);
        $deadline->update(['responsible_user_id' => $resolved]);

        app(AuditLogger::class)->log('deadline.assigned', $deadline);
    }

    public function render(): View
    {
        $deadlines = Deadline::query()
            ->with(['document.documentType', 'document.entity', 'document.person', 'responsibleUser'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy('due_date')
            ->get();

        $summary = Deadline::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('livewire.dashboard', [
            'deadlines' => $deadlines,
            'users' => User::query()->orderBy('name')->get(),
            'statuses' => DeadlineStatus::cases(),
            'summary' => $summary,
        ]);
    }
}
