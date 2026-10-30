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
                    {{-- Package prices are public, so anyone can see what a package costs before choosing it. --}}
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <flux:heading size="lg" class="text-brand-700 dark:text-brand-300">₹{{ number_format((float) $package->price, 2) }}</flux:heading>
                        @if ($package->hasDiscount())
                            <flux:text class="text-brand-400 line-through">₹{{ number_format((float) $package->subtotal, 2) }}</flux:text>
                            <flux:badge size="sm" color="green" variant="solid">
                                {{ __('Save ₹:amount (:percent% off)', ['amount' => number_format($package->discountAmount(), 2), 'percent' => $package->discountPercentage()]) }}
                            </flux:badge>
                        @endif
                    </div>

                    @if (! $package->isAvailable())
                        <flux:callout icon="no-symbol" color="red" class="mt-4">
                            <flux:callout.heading>{{ __('Currently out of stock') }}</flux:callout.heading>
                            <flux:callout.text>
                                {{ $package->products->isEmpty() ? __('This package has no products yet.') : \App\Support\StockCheck::message($package->stockShortages()) }}
                            </flux:callout.text>
                        </flux:callout>
                    @elseif (! auth()->check())
                        <flux:button :href="route('register', ['package' => $package->slug])" variant="primary" icon-trailing="arrow-right" class="mt-4" wire:navigate>
                            {{ __('Select this package') }}
                        </flux:button>
                        <flux:text size="sm" class="mt-2">
                            {{ __('You will register, then go straight to payment.') }}
                            <flux:link :href="route('login')" wire:navigate>{{ __('Already have an account? Log in') }}</flux:link>
                        </flux:text>
                    @elseif (auth()->user()->isFranchiseHolder())
                        <flux:button :href="route('packages.checkout', $package)" variant="primary" icon="shopping-cart" class="mt-4" wire:navigate>
                            {{ __('Order this Package') }}
                        </flux:button>
                    @endif
                </div>

                @if ($package->description)
                    <flux:text class="mt-6 whitespace-pre-line text-brand-700 dark:text-brand-200">{{ $package->description }}</flux:text>
                @endif

                @if ($package->hasGoodsWorth())
                    <flux:badge color="amber" variant="solid" class="mt-4">{{ __('Get ₹:amount worth of goods', ['amount' => number_format((float) $package->goods_worth, 0)]) }}</flux:badge>
                @endif

                @if ($package->inclusionList())
                    <flux:heading size="lg" class="mt-8 text-brand-950 dark:text-cream-50">{{ __('Also included') }}</flux:heading>
                    <x-package-inclusions :package="$package" class="mt-3 text-brand-900 dark:text-cream-100" />
                @endif

                <flux:heading size="lg" class="mt-8 text-brand-950 dark:text-cream-50">{{ __("What's included") }}</flux:heading>
                <ul class="mt-3 space-y-2">
                    @foreach ($package->products as $product)
                        <li class="flex items-center gap-3 border-b border-brand-100 pb-2 dark:border-brand-800">
                            <div class="size-12 shrink-0 overflow-hidden rounded-lg bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-950 dark:ring-brand-800">
                                @if ($product->image_path)
                                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover" />
                                @endif
                            </div>
                            <flux:link :href="route('products.show', $product)" wire:navigate class="grow">{{ $product->name }}</flux:link>
                            <flux:text class="shrink-0 text-brand-500 dark:text-brand-400">
                                &times;{{ $product->pivot->quantity }}
                                @if (! $package->hasFixedPrice() && auth()->check() && auth()->user()->isApproved() && (float) $product->price > 0)
                                    &middot; ₹{{ number_format((float) $product->price * $product->pivot->quantity, 2) }}
                                @endif
                            </flux:text>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts::storefront>
