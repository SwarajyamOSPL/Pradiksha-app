<x-layouts::storefront :title="$package->name">
    <div class="mx-auto max-w-5xl px-6 py-12">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('packages.index')" wire:navigate>{{ __('Packages') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $package->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="mt-6 grid gap-10 sm:grid-cols-2">
            <div class="aspect-square overflow-hidden rounded-xl bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-950 dark:ring-brand-800">
                @if ($package->image_path)
                    <img src="{{ Storage::url($package->image_path) }}" alt="{{ $package->name }}" class="h-full w-full object-cover" />
                @endif
            </div>

            <div>
                <flux:heading size="xl" class="text-brand-950 dark:text-cream-50">{{ $package->name }}</flux:heading>

                <div class="mt-4">
                    @auth
                        @if (auth()->user()->isApproved())
                            <flux:heading size="lg" class="text-brand-700 dark:text-brand-300">₹{{ number_format((float) $package->price, 2) }}</flux:heading>

                            <flux:button :href="route('packages.checkout', $package)" variant="primary" icon="shopping-cart" class="mt-4" wire:navigate>
                                {{ __('Order this Package') }}
                            </flux:button>
                        @else
                            <flux:callout icon="clock" color="amber">
                                <flux:callout.heading>{{ __('Your account is awaiting approval') }}</flux:callout.heading>
                                <flux:callout.text>{{ __("You'll see pricing and be able to order once an admin approves your account.") }}</flux:callout.text>
                            </flux:callout>
                        @endif
                    @else
                        <flux:callout icon="lock-closed" color="green">
                            <flux:callout.heading>{{ __('Login to see price and order') }}</flux:callout.heading>
                            <flux:callout.text>
                                <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
                                {{ __('or') }}
                                <flux:link :href="route('register')" wire:navigate>{{ __('register as a franchise holder') }}</flux:link>.
                            </flux:callout.text>
                        </flux:callout>
                    @endauth
                </div>

                @if ($package->description)
                    <flux:text class="mt-6 whitespace-pre-line text-brand-700 dark:text-brand-200">{{ $package->description }}</flux:text>
                @endif

                <flux:heading size="lg" class="mt-8 text-brand-950 dark:text-cream-50">{{ __("What's included") }}</flux:heading>
                <ul class="mt-3 space-y-2">
                    @foreach ($package->products as $product)
                        <li class="flex items-center justify-between border-b border-brand-100 pb-2 dark:border-brand-800">
                            <flux:link :href="route('products.show', $product)" wire:navigate>{{ $product->name }}</flux:link>
                            <flux:text class="text-brand-500 dark:text-brand-400">&times;{{ $product->pivot->quantity }}</flux:text>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts::storefront>
