<div class="space-y-6">
    <div>
        <a href="{{ route('entities.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
            <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('cockpit.entities.title') }}
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-start gap-4">
            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-slate-700 to-slate-900 text-lg font-semibold text-white">
                {{ strtoupper(substr($entity->legal_name, 0, 1)) }}
            </span>
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ $entity->legal_name }}</h1>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
                    @if ($entity->trade_name)<span>{{ $entity->trade_name }}</span>@endif
                    @if ($entity->license_number)<span class="text-slate-300">•</span><span>{{ $entity->license_number }}</span>@endif
                    @if ($entity->jurisdiction_type)<span class="text-slate-300">•</span><span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{{ ucfirst($entity->jurisdiction_type->value) }}</span>@endif
                </div>
            </div>
        </div>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-slate-700">{{ __('cockpit.entities.documents') }}</h2>
        <div class="divide-y divide-slate-50">
            @forelse ($entity->documents as $document)
                <div class="flex items-center justify-between px-5 py-3.5" wire:key="doc-{{ $document->id }}">
                    <div>
                        <div class="text-sm font-medium text-slate-800">{{ $document->documentType->name }}</div>
                        <div class="text-xs text-slate-400">
                            {{ ucfirst($document->status->value) }}@if ($document->expiry_date) · {{ $document->expiry_date->translatedFormat('d M Y') }}@endif
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-end gap-1.5">
                        @foreach ($document->deadlines as $deadline)
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $deadline->status->badgeClasses() }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $deadline->status->dotClass() }}"></span>{{ $deadline->due_date->translatedFormat('d M Y') }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-400">{{ __('cockpit.dashboard.empty') }}</p>
            @endforelse
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <h2 class="border-b border-slate-100 px-5 py-3.5 text-sm font-semibold text-slate-700">{{ __('cockpit.entities.people') }}</h2>
        <div class="divide-y divide-slate-50">
            @forelse ($entity->persons as $person)
                <div class="flex items-center gap-3 px-5 py-3.5 text-sm" wire:key="person-{{ $person->id }}">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-xs font-semibold text-slate-500">{{ strtoupper(substr($person->full_name, 0, 1)) }}</span>
                    <span class="font-medium text-slate-700">{{ $person->full_name }}</span>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-500">{{ ucfirst($person->role->value) }}</span>
                </div>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-400">{{ __('cockpit.entities.people') }}</p>
            @endforelse
        </div>
    </section>
</div>
