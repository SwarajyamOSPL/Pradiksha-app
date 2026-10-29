<?php

use App\Models\Product;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $inStockOnly = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedInStockOnly(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->active()
                ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('category', 'like', "%{$this->search}%")))
                ->when($this->inStockOnly, fn ($query) => $query->inStock())
                ->orderBy('name')
                ->paginate(12),
        ];
    }
}; ?>

<section class="w-full">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Products') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Everything currently available, at your offer prices. Add what you need to your cart.') }}</flux:text>
        </div>

        <flux:button icon="shopping-cart" :href="route('cart.index')" wire:navigate>
            {{ __('Cart') }} ({{ app(\App\Support\Cart::class)->count() }})
        </flux:button>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-4">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search products...')" icon="magnifying-glass" class="max-w-sm" />
        <flux:switch wire:model.live="inStockOnly" :label="__('Only show products in stock')" />
    </div>

    <div class="mt-6 grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-4">
        @forelse ($products as $product)
            <x-member-product-card :product="$product" wire:key="member-product-{{ $product->id }}" />
        @empty
            <flux:text class="col-span-full">{{ __('No products found.') }}</flux:text>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $products->links() }}
    </div>
</section>
