<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold">{{ __('cockpit.entities.title') }}</h1>
        <a href="{{ route('entities.import') }}" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">
            {{ __('cockpit.entities.import') }}
        </a>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
                <tr>
                    <th class="px-4 py-3">{{ __('cockpit.entities.col_name') }}</th>
                    <th class="px-4 py-3">{{ __('cockpit.entities.col_jurisdiction') }}</th>
                    <th class="px-4 py-3">{{ __('cockpit.entities.col_documents') }}</th>
                    <th class="px-4 py-3">{{ __('cockpit.entities.col_people') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($entities as $entity)
                    <tr wire:key="entity-{{ $entity->id }}">
                        <td class="px-4 py-3 font-medium">{{ $entity->legal_name }}</td>
                        <td class="px-4 py-3">{{ $entity->jurisdiction_type?->value ?? '—' }} @if($entity->authority) · {{ $entity->authority }} @endif</td>
                        <td class="px-4 py-3">{{ $entity->documents_count }}</td>
                        <td class="px-4 py-3">{{ $entity->persons_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('entities.show', $entity->id) }}" class="text-gray-600 hover:text-gray-900">{{ __('cockpit.entities.view') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('cockpit.entities.empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
