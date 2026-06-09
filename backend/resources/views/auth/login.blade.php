<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('cockpit.login.title') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 via-white to-indigo-50 antialiased">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="w-full max-w-sm">
            <div class="mb-6 flex flex-col items-center text-center">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg shadow-indigo-500/20">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12l1.8 1.8 3.2-3.6"/></svg>
                </span>
                <h1 class="mt-3 text-lg font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</h1>
                <p class="text-sm text-slate-500">{{ __('cockpit.app_tagline') }}</p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-7 shadow-xl shadow-slate-200/50">
                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-rose-50 p-3 text-sm text-rose-700">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="email">{{ __('cockpit.login.email') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700" for="password">{{ __('cockpit.login.password') }}</label>
                        <input id="password" name="password" type="password" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                        {{ __('cockpit.login.submit') }}
                    </button>
                </form>
            </div>

            <form method="POST" action="{{ route('locale.update') }}" class="mt-6 flex items-center justify-center gap-2 text-xs">
                @csrf
                <button name="locale" value="en" class="{{ app()->getLocale() === 'en' ? 'font-semibold text-slate-900' : 'text-slate-400 hover:text-slate-700' }}">English</button>
                <span class="text-slate-300">·</span>
                <button name="locale" value="ar" class="{{ app()->getLocale() === 'ar' ? 'font-semibold text-slate-900' : 'text-slate-400 hover:text-slate-700' }}">العربية</button>
            </form>
        </div>
    </div>
</body>
</html>
