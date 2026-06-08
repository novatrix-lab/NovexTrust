<div>
    <h1 class="text-xl font-semibold mb-6">{{ __('cockpit.dashboard.title') }}</h1>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @foreach ($statuses as $s)
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-2xl font-semibold">{{ $summary[$s->value] ?? 0 }}</div>
                <span class="inline-block mt-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $s->badgeClasses() }}">{{ $s->label() }}</span>
            </div>
        @endforeach
    </div>

    <div class="mb-4">
        <select wire:model.live="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">{{ __('cockpit.dashboard.filter_all') }}</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">{{ __('cockpit.dashboard.col_holder') }}</th>
                    <th class="px-4 py-3">{{ __('cockpit.dashboard.col_document') }}</th>
                    <th class="px-4 py-3">{{ __('cockpit.dashboard.col_due') }}</th>
                    <th class="px-4 py-3">{{ __('cockpit.dashboard.col_status') }}</th>
                    <th class="px-4 py-3">{{ __('cockpit.dashboard.col_responsible') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($deadlines as $deadline)
                    <tr wire:key="deadline-{{ $deadline->id }}">
                        <td class="px-4 py-3">{{ $deadline->document->entity?->legal_name ?? $deadline->document->person?->full_name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $deadline->document->documentType->name }}</td>
                        <td class="px-4 py-3">{{ $deadline->due_date->toFormattedDateString() }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium {{ $deadline->status->badgeClasses() }}">{{ $deadline->status->label() }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <select wire:change="assign({{ $deadline->id }}, $event.target.value)" class="rounded-md border border-gray-300 px-2 py-1 text-xs">
                                <option value="">{{ __('cockpit.dashboard.unassigned') }}</option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" @selected($deadline->responsible_user_id === $user->id)>{{ $user->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($deadline->status !== \App\Enums\DeadlineStatus::Done)
                                <button wire:click="markDone({{ $deadline->id }})" class="rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white hover:bg-gray-800">
                                    {{ __('cockpit.dashboard.mark_done') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('cockpit.dashboard.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
