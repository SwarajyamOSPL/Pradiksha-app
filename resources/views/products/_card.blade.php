@props(['product'])

<a href="{{ route('products.show', $product) }}" wire:navigate class="group flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-brand-100 transition hover:-translate-y-0.5 hover:shadow-lg dark:bg-brand-900 dark:ring-brand-800">
    <div class="relative aspect-square w-full overflow-hidden bg-brand-50 dark:bg-brand-950">
        @if ($product->image_path)
            <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" loading="lazy" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
        @endif

        @if ($product->category)
            <flux:badge size="sm" class="absolute top-2 left-2 border border-brand-200 bg-white/90! text-brand-700! dark:border-brand-700 dark:bg-brand-900/90! dark:text-brand-200!">
                {{ $product->category }}
            </flux:badge>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-1 p-4">
        <flux:heading size="sm" class="line-clamp-2 text-brand-950 dark:text-cream-50">{{ $product->name }}</flux:heading>

        <div class="mt-auto pt-2">
            @auth
                @if (auth()->user()->isApproved())
                    <flux:text class="text-lg font-semibold text-brand-700 dark:text-brand-300">
                        {{ $product->price !== null ? '₹'.number_format((float) $product->price, 2) : __('Price on request') }}
                    </flux:text>
                @else
                    <flux:text size="sm" class="text-brand-500 dark:text-brand-400">{{ __('Pending approval') }}</flux:text>
                @endif
            @else
                <flux:text size="sm" class="inline-flex items-center gap-1 text-brand-500 dark:text-brand-400">
                    <flux:icon.lock-closed class="size-3.5" /> {{ __('Login to see price') }}
                </flux:text>
            @endauth
        </div>
    </div>
</a>
