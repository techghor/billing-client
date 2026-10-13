@props(['header' => null])

@php
    $layout = config('billing.layout', 'layouts.app');
    $hasLayout = view()->exists($layout);
@endphp

@if ($hasLayout)
    <x-dynamic-component :component="str_replace(['layouts.', 'components.'], '', $layout)">
        @if ($header)
            <x-slot name="header">
                {{ $header }}
            </x-slot>
        @endif
        {{ $slot }}
    </x-dynamic-component>
@else
    <!DOCTYPE html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100 antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Billing & License Overview</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-800">
        <header class="bg-white border-b border-slate-200 px-6 py-4 shadow-xs">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <a href="{{ url('/') }}" class="text-xs font-semibold text-slate-600 hover:text-indigo-600 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Application
                </a>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Billing & License Client</span>
            </div>
        </header>
        <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            @if ($header)
                <div class="mb-6">
                    {{ $header }}
                </div>
            @endif
            {{ $slot }}
        </main>
    </body>
    </html>
@endif
