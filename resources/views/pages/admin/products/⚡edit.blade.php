<?php

use App\Actions\StoreUploadedImageAsWebp;
use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Product')] class extends Component
{
    use ProductValidationRules, WithFileUploads;

    public Product $product;

    public string $name = '';

    public string $slug = '';

    public ?string $category = null;

    public ?string $description = null;

    public ?string $price = null;

    public bool $is_active = true;

    public mixed $image = null;

    public ?string $currentImagePath = null;

    public function mount(Product $product): void
    {
        $this->product = $product;
        $this->name = $product->name;
        $this->slug = $product->slug;
        $this->category = $product->category;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->is_active = $product->is_active;
        $this->currentImagePath = $product->image_path;
    }

    public function save(StoreUploadedImageAsWebp $storeUploadedImageAsWebp): void
    {
        $validated = $this->validate($this->productRules($this->product->id));

        $this->product->update([
            ...collect($validated)->except('image')->toArray(),
        ]);

        if ($this->image) {
            $this->product->update([
                'image_path' => $storeUploadedImageAsWebp($this->image, 'products', $this->product->slug),
            ]);
        }

        Flux::toast(variant: 'success', text: __('Product updated.'));

        $this->redirect(route('admin.products.index'), navigate: true);
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:heading size="xl">{{ __('Edit Product') }}</flux:heading>

    <form wire:submit="save" class="mt-6">
        @include('pages::admin.products._form')

        <div class="mt-6 flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
