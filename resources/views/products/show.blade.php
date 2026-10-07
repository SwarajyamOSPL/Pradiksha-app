<x-layouts::storefront :title="$product->name">
    <div class="mx-auto max-w-5xl px-6 py-12">
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('products.index')" wire:navigate>{{ __('Products') }}</flux:breadcrumbs.item>
            @if ($product->category)
                <flux:breadcrumbs.item :href="route('products.index', ['category' => $product->category])" wire:navigate>{{ $product->category }}</flux:breadcrumbs.item>
            @endif
            <flux:breadcrumbs.item>{{ $product->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>

        <div class="mt-6 grid gap-10 sm:grid-cols-2">
            <div class="aspect-square overflow-hidden rounded-xl bg-brand-50 ring-1 ring-brand-100 dark:bg-brand-950 dark:ring-brand-800">
                @if ($product->image_path)
                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover" />
                @endif
            </div>

            <div>
                @if ($product->category)
                    <flux:badge class="mb-2 border border-brand-200 bg-white! text-brand-700! dark:border-brand-700 dark:bg-brand-900! dark:text-brand-200!">{{ $product->category }}</flux:badge>
                @endif

                <flux:heading size="xl" class="text-brand-950 dark:text-cream-50">{{ $product->name }}</flux:heading>

                <div class="mt-4">
                    @auth
                        @if (auth()->user()->isApproved())
                            <flux:heading size="lg" class="text-brand-700 dark:text-brand-300">
                                {{ $product->price !== null ? '₹'.number_format((float) $product->price, 2) : __('Price on request') }}
                            </flux:heading>

                            <form method="POST" action="{{ route('cart.add') }}" class="mt-4 flex items-end gap-3">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}" />
                                <flux:input type="number" name="quantity" value="1" min="1" :label="__('Quantity')" class="w-24" />
                                <flux:button type="submit" variant="primary" icon="shopping-cart">{{ __('Add to Basket') }}</flux:button>
                            </form>
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

                @if ($product->description)
                    <flux:text class="mt-6 whitespace-pre-line text-brand-700 dark:text-brand-200">{{ $product->description }}</flux:text>
                @endif
            </div>
        </div>
    </div>
</x-layouts::storefront>
