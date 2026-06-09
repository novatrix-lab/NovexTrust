<div class="max-w-2xl">
    <a href="{{ route('entities.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-800">
        <svg class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        {{ __('cockpit.entities.title') }}
    </a>
    <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-900">{{ __('cockpit.import.title') }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ __('cockpit.import.help') }}</p>

    @if ($result)
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            <div class="flex items-center gap-2 font-medium">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('cockpit.import.result', ['imported' => $result['imported'], 'skipped' => $result['skipped']]) }}
            </div>
            @if (! empty($result['errors']))
                <ul class="mt-2 list-disc space-y-0.5 ps-6 text-amber-700">
                    @foreach ($result['errors'] as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            @endif
        </div>
    @endif

    <form wire:submit="import" class="mt-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <label for="file" class="group flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-indigo-400 hover:bg-indigo-50/40">
            <svg class="h-8 w-8 text-slate-400 group-hover:text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L8 8m4-4l4 4M4 20h16"/></svg>
            <span class="mt-2 text-sm font-medium text-slate-600">{{ __('cockpit.import.file') }}</span>
            <span class="mt-0.5 text-xs text-slate-400">CSV</span>
            <input id="file" type="file" wire:model="file" accept=".csv,.txt" class="sr-only">
        </label>
        @if ($file)
            <p class="mt-2 text-xs text-slate-500">{{ $file->getClientOriginalName() }}</p>
        @endif
        @error('file') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror

        <button type="submit" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-60" wire:loading.attr="disabled" wire:target="import,file">
            <span wire:loading.remove wire:target="import">{{ __('cockpit.import.submit') }}</span>
            <span wire:loading wire:target="import">…</span>
        </button>
    </form>
</div>
