<?php

use App\Models\Product;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Products')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function delete(Product $product): void
    {
        try {
            $product->delete();
            Flux::toast(variant: 'success', text: __('Product deleted.'));
        } catch (QueryException) {
            Flux::toast(variant: 'danger', text: __('This product cannot be deleted because it has existing orders. Deactivate it instead.'));
        }
    }

    public function toggleActive(Product $product): void
    {
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function updatePrice(Product $product, string $price): void
    {
        $price = trim($price);

        if ($price !== '' && (! is_numeric($price) || (float) $price < 0 || (float) $price > 99999999)) {
            Flux::toast(variant: 'danger', text: __('Enter a valid price.'));

            return;
        }

        $product->update(['price' => $price === '' ? null : round((float) $price, 2)]);

        Flux::toast(variant: 'success', text: __('Price updated for :name.', ['name' => $product->name]));
    }

    public function with(): array
    {
        return [
            'products' => Product::query()
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Products') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate>
            {{ __('Add Product') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search products...')" icon="magnifying-glass" class="mt-6 max-w-sm" />

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Image') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Price') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($products as $product)
                <flux:table.row wire:key="product-{{ $product->id }}">
                    <flux:table.cell>
                        @if ($product->image_path)
                            <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-10 w-10 rounded object-cover" />
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $product->name }}</flux:table.cell>
                    <flux:table.cell>{{ $product->category }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:input
                            type="number" step="0.01" min="0" size="sm" class="w-32"
                            placeholder="{{ __('Set price') }}"
                            value="{{ $product->price }}"
                            x-on:change="$wire.updatePrice({{ $product->id }}, $event.target.value)"
                            aria-label="{{ __('Price for :name', ['name' => $product->name]) }}"
                        >
                            <x-slot name="iconLeading"><span class="text-sm text-zinc-500">₹</span></x-slot>
                        </flux:input>
                    </flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :variant="$product->is_active ? 'solid' : 'outline'" wire:click="toggleActive({{ $product->id }})" class="cursor-pointer">
                            {{ $product->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="ghost" :href="route('admin.products.edit', $product)" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:modal.trigger name="delete-product-{{ $product->id }}">
                                <flux:button size="sm" variant="danger">{{ __('Delete') }}</flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-product-{{ $product->id }}" class="max-w-md">
                            <div class="space-y-6">
                                <flux:heading size="lg">{{ __('Delete :name?', ['name' => $product->name]) }}</flux:heading>
                                <flux:subheading>{{ __('This cannot be undone.') }}</flux:subheading>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" wire:click="delete({{ $product->id }})">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ __('No products found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</section>
