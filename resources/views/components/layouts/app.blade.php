<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'OmniReply' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-100 text-gray-900 antialiased">
    <div class="flex h-full flex-col">
        <header class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3">
            <div class="flex items-center gap-6">
                <span class="text-lg font-semibold text-amber-600">OmniReply</span>
                <nav class="flex items-center gap-4 text-sm">
                    @foreach (['app.inbox' => 'Inbox', 'app.catalog' => 'Catalog', 'app.knowledge' => 'Knowledge'] as $route => $label)
                        <a href="{{ route($route) }}" wire:navigate
                           @class(['font-medium', 'text-amber-600' => request()->routeIs($route), 'text-gray-500 hover:text-gray-900' => ! request()->routeIs($route)])>{{ $label }}</a>
                    @endforeach
                </nav>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-400">{{ auth()->user()?->currentWorkspace?->name }}</span>
                <form method="POST" action="{{ route('app.logout') }}">
                    @csrf
                    <button class="text-sm text-gray-500 hover:text-gray-900">Log out</button>
                </form>
            </div>
        </header>

        <main class="min-h-0 flex-1">
            {{ $slot }}
        </main>
    </div>
    @livewireScripts
</body>
</html>
