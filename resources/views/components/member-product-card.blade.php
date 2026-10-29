@props(['product'])

@php
    $hasPrice = $product->price !== null && (float) $product->price > 0;
@endphp

<div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-brand-100 dark:bg-brand-900 dark:ring-brand-800" data-product="{{ $product->slug }}">
    <a href="{{ route('products.show', $product) }}" wire:navigate class="relative block aspect-square w-full overflow-hidden bg-brand-50 dark:bg-brand-950">
        @if ($product->image_path)
            <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover" />
        @endif

        @if ($product->hasOffer())
            <flux:badge size="sm" color="green" variant="solid" class="absolute top-2 left-2">{{ __(':percent% off', ['percent' => $product->savingsPercentage()]) }}</flux:badge>
        @endif
    </a>

    <div class="flex flex-1 flex-col gap-2 p-4">
        <a href="{{ route('products.show', $product) }}" wire:navigate>
            <flux:heading size="sm" class="line-clamp-2 text-brand-950 dark:text-cream-50">{{ $product->name }}</flux:heading>
        </a>

        <div class="mt-auto">
            @if ($hasPrice)
                <p class="text-xs tracking-wide text-zinc-500 uppercase dark:text-zinc-400">{{ __('Offer price') }}</p>
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <span class="text-xl font-semibold text-brand-700 dark:text-brand-300">₹{{ number_format((float) $product->price, 2) }}</span>
                    @if ($product->hasOffer())
                        <span class="text-sm text-zinc-400 line-through">MRP ₹{{ number_format((float) $product->mrp, 2) }}</span>
                    @endif
                </div>
            @else
                <flux:text class="font-medium">{{ __('Price on request') }}</flux:text>
            @endif

            <flux:text size="sm" class="mt-1 font-medium {{ $product->stockTextClasses() }}">{{ __($product->stockLabel()) }}</flux:text>
        </div>

        @if ($product->isInStock() && $hasPrice)
            <form method="POST" action="{{ route('cart.add') }}" class="mt-1 flex items-center gap-2">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}" />
                <flux:input
                    type="number" name="quantity" value="1" min="1" max="{{ min(\App\Support\Cart::MAX_QUANTITY, $product->stock) }}"
                    class="w-20" aria-label="{{ __('Quantity of :name', ['name' => $product->name]) }}"
                />
                <flux:button type="submit" variant="primary" icon="shopping-cart" class="grow">{{ __('Add to cart') }}</flux:button>
            </form>
        @else
            <flux:button variant="filled" class="mt-1 w-full" disabled>{{ $product->isInStock() ? __('Not available') : __('Out of stock') }}</flux:button>
        @endif
    </div>
</div>
