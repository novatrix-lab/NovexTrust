<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 flex h-14 items-center justify-between">
            <div class="flex items-center gap-6">
                <span class="font-semibold">{{ config('app.name') }}</span>
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('cockpit.nav.dashboard') }}</a>
                <a href="{{ route('entities.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('cockpit.nav.entities') }}</a>
            </div>
            <div class="flex items-center gap-4">
                <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-1 text-xs">
                    @csrf
                    <button name="locale" value="en" class="{{ app()->getLocale() === 'en' ? 'font-semibold text-gray-900' : 'text-gray-400 hover:text-gray-700' }}">EN</button>
                    <span class="text-gray-300">|</span>
                    <button name="locale" value="ar" class="{{ app()->getLocale() === 'ar' ? 'font-semibold text-gray-900' : 'text-gray-400 hover:text-gray-700' }}">ع</button>
                </form>
                <span class="text-sm text-gray-500">{{ auth()->user()?->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">{{ __('cockpit.nav.logout') }}</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
