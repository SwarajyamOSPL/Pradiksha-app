<div class="flex flex-col gap-6">
    <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

    <flux:input wire:model="slug" :label="__('Slug')" type="text" required description="{{ __('Used in the package URL.') }}" />

    <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

    <flux:input wire:model="price" :label="__('Package price (₹)')" type="number" step="0.01" min="0" required description="{{ __('The bundle price shown to franchise holders.') }}" />

    <div>
        <flux:input type="file" wire:model="image" :label="__('Image')" accept="image/png,image/jpeg,image/webp" />
        @if ($currentImagePath ?? null)
            <img src="{{ Storage::url($currentImagePath) }}" alt="{{ $name }}" class="mt-2 h-24 w-24 rounded-lg object-cover" />
        @endif
    </div>

    <flux:switch wire:model="is_active" :label="__('Active (visible on the storefront)')" />

    <div>
        <flux:label>{{ __('Products in this package') }}</flux:label>

        <div class="mt-2 flex flex-col gap-3">
            @foreach ($products as $index => $row)
                <div class="flex items-end gap-3" wire:key="package-product-row-{{ $index }}">
                    <flux:select wire:model="products.{{ $index }}.product_id" :label="$index === 0 ? __('Product') : ''" class="grow">
                        <flux:select.option value="">{{ __('Select a product') }}</flux:select.option>
                        @foreach ($availableProducts as $product)
                            <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="products.{{ $index }}.quantity" :label="$index === 0 ? __('Qty') : ''" type="number" min="1" class="w-24" />

                    <flux:button variant="ghost" icon="trash" wire:click="removeProductRow({{ $index }})" />
                </div>
            @endforeach
        </div>

        <flux:button variant="ghost" size="sm" icon="plus" wire:click="addProductRow" class="mt-3">
            {{ __('Add product') }}
        </flux:button>
    </div>
</div>
