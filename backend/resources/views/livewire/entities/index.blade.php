<div>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ __('cockpit.entities.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $entities->count() }}</p>
        </div>
        <a href="{{ route('entities.import') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/></svg>
            {{ __('cockpit.entities.import') }}
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead>
                    <tr class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 text-start">{{ __('cockpit.entities.col_name') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('cockpit.entities.col_jurisdiction') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('cockpit.entities.col_documents') }}</th>
                        <th class="px-5 py-3 text-start">{{ __('cockpit.entities.col_people') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($entities as $entity)
                        <tr wire:key="entity-{{ $entity->id }}" class="transition hover:bg-slate-50/70">
                            <td class="px-5 py-3.5">
                                <a href="{{ route('entities.show', $entity->id) }}" class="font-medium text-slate-800 hover:text-indigo-600">{{ $entity->legal_name }}</a>
                                @if ($entity->trade_name)<div class="text-xs text-slate-400">{{ $entity->trade_name }}</div>@endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($entity->jurisdiction_type)
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ ucfirst($entity->jurisdiction_type->value) }}@if($entity->authority) · {{ $entity->authority }}@endif</span>
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $entity->documents_count }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $entity->persons_count }}</td>
                            <td class="px-5 py-3.5 text-end">
                                <a href="{{ route('entities.show', $entity->id) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ __('cockpit.entities.view') }}
                                    <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-slate-400">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14"/></svg>
                                </div>
                                <p class="mt-3 text-sm text-slate-500">{{ __('cockpit.entities.empty') }}</p>
                                <a href="{{ route('entities.import') }}" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700">{{ __('cockpit.entities.import') }}</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
