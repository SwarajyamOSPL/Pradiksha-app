<?php

use App\Actions\StoreUploadedImageAsWebp;
use App\Concerns\ProductValidationRules;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Create Product')] class extends Component
{
    use ProductValidationRules, WithFileUploads;

    public string $name = '';

    public string $slug = '';

    public ?string $category = null;

    public ?string $description = null;

    public ?string $price = null;

    public bool $is_active = true;

    public mixed $image = null;

    public ?string $currentImagePath = null;

    private bool $slugManuallyEdited = false;

    public function updatedName(): void
    {
        if (! $this->slugManuallyEdited) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
    }

    public function save(StoreUploadedImageAsWebp $storeUploadedImageAsWebp): void
    {
        $validated = $this->validate($this->productRules());

        $product = Product::create([
            ...collect($validated)->except('image')->toArray(),
        ]);

        if ($this->image) {
            $product->update([
                'image_path' => $storeUploadedImageAsWebp($this->image, 'products', $product->slug),
            ]);
        }

        Flux::toast(variant: 'success', text: __('Product created.'));

        $this->redirect(route('admin.products.index'), navigate: true);
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:heading size="xl">{{ __('Create Product') }}</flux:heading>

    <form wire:submit="save" class="mt-6">
        @include('pages::admin.products._form')

        <div class="mt-6 flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.products.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
