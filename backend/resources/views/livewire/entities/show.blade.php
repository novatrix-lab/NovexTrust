<div class="space-y-8">
    <div>
        <a href="{{ route('entities.index') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; {{ __('cockpit.entities.title') }}</a>
        <h1 class="text-xl font-semibold mt-2">{{ $entity->legal_name }}</h1>
        <p class="text-sm text-gray-500">
            {{ $entity->trade_name }}
            @if($entity->license_number) · {{ $entity->license_number }} @endif
            @if($entity->jurisdiction_type) · {{ $entity->jurisdiction_type->value }} @endif
        </p>
    </div>

    <section>
        <h2 class="text-sm font-semibold uppercase text-gray-500 mb-2">{{ __('cockpit.entities.documents') }}</h2>
        <div class="rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
            @forelse ($entity->documents as $document)
                <div class="px-4 py-3 flex items-center justify-between" wire:key="doc-{{ $document->id }}">
                    <div>
                        <div class="font-medium text-sm">{{ $document->documentType->name }}</div>
                        <div class="text-xs text-gray-500">
                            {{ $document->status->value }}
                            @if($document->expiry_date) · expires {{ $document->expiry_date->toFormattedDateString() }} @endif
                        </div>
                    </div>
                    <div class="flex gap-1.5">
                        @foreach ($document->deadlines as $deadline)
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $deadline->status->badgeClasses() }}">
                                {{ $deadline->due_date->toFormattedDateString() }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-500">{{ __('cockpit.dashboard.empty') }}</div>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="text-sm font-semibold uppercase text-gray-500 mb-2">{{ __('cockpit.entities.people') }}</h2>
        <div class="rounded-lg border border-gray-200 bg-white divide-y divide-gray-100">
            @forelse ($entity->persons as $person)
                <div class="px-4 py-3 text-sm" wire:key="person-{{ $person->id }}">
                    {{ $person->full_name }} <span class="text-gray-400">· {{ $person->role->value }}</span>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-500">{{ __('cockpit.entities.people') }}</div>
            @endforelse
        </div>
    </section>
</div>
