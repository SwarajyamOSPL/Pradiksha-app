<x-layouts::storefront :title="__('Your Basket')">
    <div class="mx-auto max-w-4xl px-6 py-12">
        <flux:heading size="xl" class="text-brand-950 dark:text-cream-50">{{ __('Your Basket') }}</flux:heading>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        @if ($items->isEmpty())
            <flux:text class="mt-6 text-brand-700 dark:text-brand-200">
                {{ __('Your basket is empty.') }}
                <flux:link :href="route('products.index')" wire:navigate>{{ __('Browse products') }}</flux:link>
            </flux:text>
        @else
            <div class="mt-8 flex flex-col gap-4">
                @foreach ($items as $item)
                    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-brand-100 bg-white p-4 dark:border-brand-800 dark:bg-brand-900">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-brand-50 dark:bg-brand-950">
                            @if ($item['product']->image_path)
                                <img src="{{ Storage::url($item['product']->image_path) }}" alt="{{ $item['product']->name }}" class="h-full w-full object-cover" />
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <flux:link :href="route('products.show', $item['product'])" wire:navigate class="font-medium">
                                {{ $item['product']->name }}
                            </flux:link>
                            <flux:text class="text-brand-500 dark:text-brand-400">
                                ₹{{ number_format((float) ($item['product']->price ?? 0), 2) }} {{ __('each') }}
                            </flux:text>
                        </div>

                        <form method="POST" action="{{ route('cart.update', $item['product']) }}" class="flex items-center gap-2">
                            @csrf
                            @method('PATCH')
                            <flux:input type="number" name="quantity" value="{{ $item['quantity'] }}" min="0" class="w-20" />
                            <flux:button size="sm" type="submit" variant="filled">{{ __('Update') }}</flux:button>
                        </form>

                        <form method="POST" action="{{ route('cart.destroy', $item['product']) }}">
                            @csrf
                            @method('DELETE')
                            <flux:button size="sm" type="submit" variant="danger">{{ __('Remove') }}</flux:button>
                        </form>

                        <flux:text class="w-24 shrink-0 text-end font-semibold text-brand-700 dark:text-brand-300">
                            ₹{{ number_format((float) ($item['product']->price ?? 0) * $item['quantity'], 2) }}
                        </flux:text>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 flex items-center justify-between border-t border-brand-100 pt-6 dark:border-brand-800">
                <flux:heading size="lg" class="text-brand-950 dark:text-cream-50">{{ __('Total') }}</flux:heading>
                <flux:heading size="lg" class="text-brand-700 dark:text-brand-300">₹{{ number_format($total, 2) }}</flux:heading>
            </div>

            <div class="mt-6 flex justify-end">
                <flux:button :href="route('checkout')" variant="primary" icon="arrow-right" wire:navigate>{{ __('Proceed to Checkout') }}</flux:button>
            </div>
        @endif
    </div>
</x-layouts::storefront>
