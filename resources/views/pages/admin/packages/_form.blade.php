<div class="flex flex-col gap-6">
    <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

    <flux:input wire:model="slug" :label="__('Slug')" type="text" required description="{{ __('Used in the package URL.') }}" />

    <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

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
                    <flux:select wire:model.live="products.{{ $index }}.product_id" :label="$index === 0 ? __('Product') : ''" class="grow">
                        <flux:select.option value="">{{ __('Select a product') }}</flux:select.option>
                        @foreach ($availableProducts as $product)
                            <flux:select.option value="{{ $product->id }}">
                                {{ $product->name }} &mdash; {{ (float) $product->price > 0 ? '₹'.number_format((float) $product->price, 2) : __('no price set') }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model.live.debounce.400ms="products.{{ $index }}.quantity" :label="$index === 0 ? __('Qty') : ''" type="number" min="1" class="w-24" />

                    <flux:button variant="ghost" icon="trash" wire:click="removeProductRow({{ $index }})" />
                </div>
            @endforeach
        </div>

        <flux:button variant="ghost" size="sm" icon="plus" wire:click="addProductRow" class="mt-3">
            {{ __('Add product') }}
        </flux:button>
    </div>

    @php($pricing = $this->pricingSummary())

    <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Pricing') }}</flux:heading>
        <flux:text size="sm" class="mt-1">{{ __('By default the package price is worked out from the product prices and quantities above. Fill in a fixed price to sell the package at exactly that amount instead.') }}</flux:text>

        <flux:input
            wire:model.live.debounce.400ms="fixed_price"
            :label="__('Fixed package price (₹), optional')"
            type="number" step="0.01" min="0" class="mt-4"
            description="{{ __('Leave empty to use the calculated price and discount below.') }}"
        />

        @unless ($pricing['fixed'])
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="discount_type" :label="__('Discount')">
                    <flux:select.option value="">{{ __('No discount') }}</flux:select.option>
                    @foreach (\App\Enums\DiscountType::cases() as $type)
                        <flux:select.option value="{{ $type->value }}">{{ $type->label() }}</flux:select.option>
                    @endforeach
                </flux:select>

                @if ($discount_type)
                    <flux:input
                        wire:model.live.debounce.400ms="discount_value"
                        :label="$discount_type === 'percent' ? __('Discount (%)') : __('Discount amount (₹)')"
                        type="number" step="0.01" min="0" :max="$discount_type === 'percent' ? 100 : null"
                    />
                @endif
            </div>
        @endunless

        @if (count($pricing['unpriced']) > 0)
            <flux:callout icon="exclamation-triangle" color="amber" class="mt-4">
                <flux:callout.heading>{{ __('Some products have no price yet') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Set a price for these products first, otherwise they count as ₹0:') }}
                    {{ implode(', ', $pricing['unpriced']) }}
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="mt-5 flex flex-col gap-2 text-sm">
            @unless ($pricing['fixed'])
                <div class="flex items-center justify-between">
                    <flux:text>{{ __('Products total') }}</flux:text>
                    <flux:text>₹{{ number_format($pricing['subtotal'], 2) }}</flux:text>
                </div>
            @endunless
            @if ($pricing['discount'] > 0)
                <div class="flex items-center justify-between">
                    <flux:text class="text-green-600 dark:text-green-400">{{ __('Discount') }}</flux:text>
                    <flux:text class="text-green-600 dark:text-green-400">-₹{{ number_format($pricing['discount'], 2) }}</flux:text>
                </div>
            @endif
            <div class="mt-1 flex items-center justify-between border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <flux:heading>{{ __('Package price') }}</flux:heading>
                <flux:heading size="lg">₹{{ number_format($pricing['price'], 2) }}</flux:heading>
            </div>
        </div>
    </div>
</div>
