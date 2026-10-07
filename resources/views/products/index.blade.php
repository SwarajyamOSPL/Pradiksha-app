<x-layouts::storefront :title="__('Products')">
    <div class="mx-auto max-w-6xl px-6 py-12">
        <flux:heading size="xl" class="text-brand-950 dark:text-cream-50">{{ __('Products') }}</flux:heading>

        @unless (auth()->check() && auth()->user()->isApproved())
            <flux:callout icon="lock-closed" color="green" class="mt-4">
                <flux:callout.heading>{{ __('Prices are for franchise holders only') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Register and get approved to see franchise pricing and place orders.') }}
                </flux:callout.text>
            </flux:callout>
        @endunless

        @if ($categories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2">
                <flux:link :href="route('products.index')" wire:navigate>
                    <flux:badge class="{{ $selectedCategory === null ? 'bg-brand-600! text-white!' : 'border border-brand-200 bg-white! text-brand-700! dark:border-brand-700 dark:bg-brand-900! dark:text-brand-200!' }}">
                        {{ __('All') }}
                    </flux:badge>
                </flux:link>
                @foreach ($categories as $category)
                    <flux:link :href="route('products.index', ['category' => $category])" wire:navigate class="max-w-full">
                        <flux:badge class="max-w-full text-wrap! {{ $selectedCategory === $category ? 'bg-brand-600! text-white!' : 'border border-brand-200 bg-white! text-brand-700! dark:border-brand-700 dark:bg-brand-900! dark:text-brand-200!' }}">
                            {{ $category }}
                        </flux:badge>
                    </flux:link>
                @endforeach
            </div>
        @endif

        <div class="mt-8 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($products as $product)
                @include('products._card', ['product' => $product])
            @empty
                <flux:text class="col-span-full">{{ __('No products found.') }}</flux:text>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </div>
</x-layouts::storefront>
