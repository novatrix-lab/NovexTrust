<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('cockpit.login.title') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-sm bg-white rounded-xl shadow-sm border border-gray-200 p-8">
        <h1 class="text-lg font-semibold mb-1">{{ config('app.name') }}</h1>
        <p class="text-sm text-gray-500 mb-6">{{ __('cockpit.app_tagline') }}</p>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium mb-1" for="email">{{ __('cockpit.login.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" for="password">{{ __('cockpit.login.password') }}</label>
                <input id="password" name="password" type="password" required
                       class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none">
            </div>
            <button type="submit" class="w-full rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white hover:bg-gray-800">
                {{ __('cockpit.login.submit') }}
            </button>
        </form>

        <form method="POST" action="{{ route('locale.update') }}" class="mt-6 flex items-center justify-center gap-2 text-xs">
            @csrf
            <button name="locale" value="en" class="{{ app()->getLocale() === 'en' ? 'font-semibold text-gray-900' : 'text-gray-400 hover:text-gray-700' }}">English</button>
            <span class="text-gray-300">|</span>
            <button name="locale" value="ar" class="{{ app()->getLocale() === 'ar' ? 'font-semibold text-gray-900' : 'text-gray-400 hover:text-gray-700' }}">العربية</button>
        </form>
    </div>
</body>
</html>
