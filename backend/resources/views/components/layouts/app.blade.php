<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    @php($navLink = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition')
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-sm">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.5 12l1.8 1.8 3.2-3.6"/></svg>
                    </span>
                    <span class="text-base font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                </a>
                <nav class="hidden items-center gap-1 sm:flex">
                    <a href="{{ route('dashboard') }}" class="{{ $navLink }} {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
                        {{ __('cockpit.nav.dashboard') }}
                    </a>
                    <a href="{{ route('entities.index') }}" class="{{ $navLink }} {{ request()->routeIs('entities.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01"/></svg>
                        {{ __('cockpit.nav.entities') }}
                    </a>
                </nav>
            </div>
            <div class="flex items-center gap-3">
                <form method="POST" action="{{ route('locale.update') }}" class="hidden items-center rounded-lg border border-slate-200 p-0.5 text-xs sm:flex">
                    @csrf
                    <button name="locale" value="en" class="rounded-md px-2 py-1 {{ app()->getLocale() === 'en' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}">EN</button>
                    <button name="locale" value="ar" class="rounded-md px-2 py-1 {{ app()->getLocale() === 'ar' ? 'bg-slate-900 text-white' : 'text-slate-500 hover:text-slate-800' }}">ع</button>
                </form>
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-slate-200 text-xs font-semibold text-slate-600">{{ strtoupper(substr(auth()->user()?->name ?? '?', 0, 1)) }}</span>
                    <span class="hidden text-sm font-medium text-slate-700 md:inline">{{ auth()->user()?->name }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="{{ __('cockpit.nav.logout') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H3m0 0l4-4m-4 4l4 4M21 3v18"/></svg>
                    </button>
                </form>
            </div>
        </div>
        <nav class="flex gap-1 border-t border-slate-100 px-4 py-2 sm:hidden">
            <a href="{{ route('dashboard') }}" class="{{ $navLink }} {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">{{ __('cockpit.nav.dashboard') }}</a>
            <a href="{{ route('entities.index') }}" class="{{ $navLink }} {{ request()->routeIs('entities.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600' }}">{{ __('cockpit.nav.entities') }}</a>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
