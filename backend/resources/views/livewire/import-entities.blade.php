<div class="max-w-xl">
    <a href="{{ route('entities.index') }}" class="text-sm text-gray-500 hover:text-gray-900">&larr; {{ __('cockpit.entities.title') }}</a>
    <h1 class="text-xl font-semibold mt-2 mb-1">{{ __('cockpit.import.title') }}</h1>
    <p class="text-sm text-gray-500 mb-6">{{ __('cockpit.import.help') }}</p>

    @if ($result)
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">
            {{ __('cockpit.import.result', ['imported' => $result['imported'], 'skipped' => $result['skipped']]) }}
        </div>
        @if (! empty($result['errors']))
            <ul class="mb-4 list-disc ps-5 text-sm text-amber-700">
                @foreach ($result['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    @endif

    <form wire:submit="import" class="space-y-4 rounded-lg border border-gray-200 bg-white p-6">
        <div>
            <label class="block text-sm font-medium mb-1" for="file">{{ __('cockpit.import.file') }}</label>
            <input id="file" type="file" wire:model="file" accept=".csv,.txt" class="block w-full text-sm">
            @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">
            <span wire:loading.remove wire:target="import">{{ __('cockpit.import.submit') }}</span>
            <span wire:loading wire:target="import">…</span>
        </button>
    </form>
</div>
