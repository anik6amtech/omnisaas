<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'OmniReply' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-100 text-gray-900 antialiased">
    <div class="flex min-h-full items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-6 text-center">
                <span class="text-2xl font-bold text-amber-600">OmniReply</span>
                <p class="mt-1 text-sm text-gray-500">Sign in to your inbox</p>
            </div>
            {{ $slot }}
        </div>
    </div>
    @livewireScripts
</body>
</html>
