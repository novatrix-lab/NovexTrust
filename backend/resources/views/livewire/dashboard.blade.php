<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('cockpit.dashboard.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $deadlines->count() }} {{ __('cockpit.entities.deadlines') }}</p>
        </div>
    </div>

    {{-- Traffic-light summary cards (click to filter) --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($statuses as $s)
            <button type="button" wire:click="$set('status', '{{ $status === $s->value ? '' : $s->value }}')"
                    class="group rounded-2xl border bg-white p-4 text-start shadow-sm transition hover:shadow-md {{ $status === $s->value ? 'border-indigo-400 ring-2 ring-indigo-100' : 'border-slate-200' }}">
                <div class="flex items-center justify-between">
                    <span class="grid h-9 w-9 place-items-center rounded-xl {{ $s->cardAccent() }}">
                        <span class="h-2.5 w-2.5 rounded-full {{ $s->dotClass() }}"></span>
                    </span>
                    <span class="text-2xl font-semibold text-slate-900">{{ $summary[$s->value] ?? 0 }}</span>
                </div>
                <p class="mt-2 text-sm font-medium text-slate-600">{{ $s->label() }}</p>
            </button>
        @endforeach
    </div>

    {{-- Deadlines table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
            <h2 class="text-sm font-semibold text-slate-700">{{ __('cockpit.nav.dashboard') }}</h2>
            <select wire:model.live="status" class="rounded-lg border-slate-300 bg-slate-50 py-1.5 text-sm text-slate-600 focus:border-indigo-400 focus:ring-indigo-200">
                <option value="">{{ __('cockpit.dashboard.filter_all') }}</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead>
                    <tr class="text-start text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 text-start font-semibold">{{ __('cockpit.dashboard.col_holder') }}</th>
                        <th class="px-5 py-3 text-start font-semibold">{{ __('cockpit.dashboard.col_document') }}</th>
                        <th class="px-5 py-3 text-start font-semibold">{{ __('cockpit.dashboard.col_due') }}</th>
                        <th class="px-5 py-3 text-start font-semibold">{{ __('cockpit.dashboard.col_status') }}</th>
                        <th class="px-5 py-3 text-start font-semibold">{{ __('cockpit.dashboard.col_responsible') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($deadlines as $deadline)
                        <tr wire:key="deadline-{{ $deadline->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-3.5 font-medium text-slate-800">{{ $deadline->document->entity?->legal_name ?? $deadline->document->person?->full_name ?? '—' }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $deadline->document->documentType->name }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $deadline->due_date->translatedFormat('d M Y') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $deadline->status->badgeClasses() }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $deadline->status->dotClass() }}"></span>{{ $deadline->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <select wire:change="assign({{ $deadline->id }}, $event.target.value)" class="rounded-lg border-slate-200 bg-white py-1 text-xs text-slate-600 focus:border-indigo-400 focus:ring-indigo-200">
                                    <option value="">{{ __('cockpit.dashboard.unassigned') }}</option>
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}" @selected($deadline->responsible_user_id === $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-5 py-3.5 text-end">
                                @if ($deadline->status !== \App\Enums\DeadlineStatus::Done)
                                    <button wire:click="markDone({{ $deadline->id }})" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-slate-700">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        {{ __('cockpit.dashboard.mark_done') }}
                                    </button>
                                @else
                                    <span class="text-xs text-slate-400">✓</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6L19 9.4V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <p class="mt-3 text-sm text-slate-500">{{ __('cockpit.dashboard.empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
