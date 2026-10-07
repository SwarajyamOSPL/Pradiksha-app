<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-linear-to-b from-brand-100 via-cream-50 to-cream-50 antialiased dark:from-brand-900 dark:via-brand-950 dark:to-brand-950">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <x-app-logo-icon class="h-12 w-auto" />
                    <span class="sr-only">{{ config('app.name') }}</span>
                </a>
                <div class="mt-4 flex flex-col gap-6 rounded-xl border border-brand-100 bg-white/80 p-6 shadow-sm backdrop-blur-sm dark:border-brand-800 dark:bg-brand-900/60">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
