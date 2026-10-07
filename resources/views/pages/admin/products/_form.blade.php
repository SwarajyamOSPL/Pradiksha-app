<div class="flex flex-col gap-6">
    <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

    <flux:input wire:model="slug" :label="__('Slug')" type="text" required description="{{ __('Used in the product URL.') }}" />

    <flux:input wire:model="category" :label="__('Category')" type="text" />

    <flux:textarea wire:model="description" :label="__('Description')" rows="4" />

    <flux:input wire:model="price" :label="__('Price (₹)')" type="number" step="0.01" min="0" description="{{ __('Visible to logged-in franchise holders only.') }}" />

    <div>
        <flux:input type="file" wire:model="image" :label="__('Image')" accept="image/png,image/jpeg,image/webp" />
        @if ($currentImagePath ?? null)
            <img src="{{ Storage::url($currentImagePath) }}" alt="{{ $name }}" class="mt-2 h-24 w-24 rounded-lg object-cover" />
        @endif
    </div>

    <flux:switch wire:model="is_active" :label="__('Active (visible on the storefront)')" />
</div>
