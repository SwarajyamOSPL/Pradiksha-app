@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen overflow-x-hidden bg-cream-50 text-brand-950 antialiased dark:bg-brand-950 dark:text-cream-50">
        <header class="sticky top-0 z-20 border-b border-brand-100 bg-cream-50/90 backdrop-blur-sm dark:border-brand-800 dark:bg-brand-950/90">
            <div class="relative mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4" x-data="{ mobileOpen: false }">
                <x-app-logo href="{{ route('home') }}" wire:navigate class="min-w-0 shrink" />

                <nav class="hidden items-center gap-8 md:flex">
                    <flux:link :href="route('products.index')" variant="ghost" class="font-medium text-brand-800 hover:text-brand-600 dark:text-cream-100 dark:hover:text-brand-300" wire:navigate>{{ __('Products') }}</flux:link>
                    <flux:link :href="route('packages.index')" variant="ghost" class="font-medium text-brand-800 hover:text-brand-600 dark:text-cream-100 dark:hover:text-brand-300" wire:navigate>{{ __('Packages') }}</flux:link>
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        @if (auth()->user()->isApproved())
                            <flux:button :href="route('cart.index')" variant="ghost" icon="shopping-cart" wire:navigate>
                                {{ __('Basket') }} ({{ app(\App\Support\Cart::class)->count() }})
                            </flux:button>
                            <flux:button :href="route('orders.index')" variant="ghost" wire:navigate>{{ __('My Orders') }}</flux:button>
                        @endif
                        <flux:button :href="route('dashboard')" variant="primary" wire:navigate>{{ __('Dashboard') }}</flux:button>
                    @else
                        <flux:button :href="route('login')" variant="ghost" wire:navigate>{{ __('Log in') }}</flux:button>
                        <flux:button :href="route('register')" variant="primary" wire:navigate>{{ __('Register') }}</flux:button>
                    @endauth
                </div>

                <button type="button" class="text-brand-800 md:hidden dark:text-cream-100" @click="mobileOpen = !mobileOpen" aria-label="{{ __('Toggle menu') }}">
                    <flux:icon.bars-2 class="size-6" />
                </button>

                <div x-show="mobileOpen" x-cloak class="absolute inset-x-0 top-[73px] z-10 flex flex-col gap-2 border-b border-brand-100 bg-cream-50 p-4 shadow-lg md:hidden dark:border-brand-800 dark:bg-brand-950">
                    <flux:link :href="route('products.index')" wire:navigate>{{ __('Products') }}</flux:link>
                    <flux:link :href="route('packages.index')" wire:navigate>{{ __('Packages') }}</flux:link>
                    @auth
                        @if (auth()->user()->isApproved())
                            <flux:link :href="route('cart.index')" wire:navigate>
                                {{ __('Basket') }} ({{ app(\App\Support\Cart::class)->count() }})
                            </flux:link>
                            <flux:link :href="route('orders.index')" wire:navigate>{{ __('My Orders') }}</flux:link>
                        @endif
                        <flux:button :href="route('dashboard')" variant="primary" wire:navigate>{{ __('Dashboard') }}</flux:button>
                    @else
                        <flux:button :href="route('login')" variant="ghost" wire:navigate>{{ __('Log in') }}</flux:button>
                        <flux:button :href="route('register')" variant="primary" wire:navigate>{{ __('Register') }}</flux:button>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-20 border-t border-brand-100 bg-brand-50 dark:border-brand-800 dark:bg-brand-900">
            <div class="mx-auto max-w-6xl px-6 py-12">
                <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="col-span-2">
                        <x-app-logo />
                        <flux:text class="mt-4 max-w-sm text-brand-700 dark:text-brand-200">
                            {{ __('Authentic ayurvedic and herbal wellness products, available to registered franchise partners across India at franchise pricing.') }}
                        </flux:text>
                    </div>

                    <div>
                        <flux:heading size="sm" class="text-brand-900 uppercase dark:text-cream-50">{{ __('Shop') }}</flux:heading>
                        <ul class="mt-4 space-y-2">
                            <li><flux:link :href="route('products.index')" variant="subtle" wire:navigate>{{ __('Products') }}</flux:link></li>
                            <li><flux:link :href="route('packages.index')" variant="subtle" wire:navigate>{{ __('Packages') }}</flux:link></li>
                        </ul>
                    </div>

                    <div>
                        <flux:heading size="sm" class="text-brand-900 uppercase dark:text-cream-50">{{ __('Account') }}</flux:heading>
                        <ul class="mt-4 space-y-2">
                            @guest
                                <li><flux:link :href="route('register')" variant="subtle" wire:navigate>{{ __('Become a Franchise Partner') }}</flux:link></li>
                                <li><flux:link :href="route('login')" variant="subtle" wire:navigate>{{ __('Log in') }}</flux:link></li>
                            @else
                                <li><flux:link :href="route('dashboard')" variant="subtle" wire:navigate>{{ __('Dashboard') }}</flux:link></li>
                            @endguest
                        </ul>
                    </div>
                </div>

                <div class="mt-10 border-t border-brand-100 pt-6 text-sm text-brand-600 dark:border-brand-800 dark:text-brand-300">
                    &copy; {{ now()->year }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                </div>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
