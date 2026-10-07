<?php

use App\Actions\StoreUploadedImageAsWebp;
use App\Concerns\PackageValidationRules;
use App\Models\Package;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Edit Package')] class extends Component
{
    use PackageValidationRules, WithFileUploads;

    public Package $package;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public ?string $price = null;

    public bool $is_active = true;

    public mixed $image = null;

    public ?string $currentImagePath = null;

    /** @var array<int, array{product_id: string, quantity: int}> */
    public array $products = [];

    public Collection $availableProducts;

    public function mount(Package $package): void
    {
        $this->package = $package;
        $this->name = $package->name;
        $this->slug = $package->slug;
        $this->description = $package->description;
        $this->price = $package->price;
        $this->is_active = $package->is_active;
        $this->currentImagePath = $package->image_path;
        $this->availableProducts = Product::query()->orderBy('name')->get();

        $this->products = $package->products->map(fn (Product $product) => [
            'product_id' => (string) $product->id,
            'quantity' => $product->pivot->quantity,
        ])->values()->all();

        if (empty($this->products)) {
            $this->products[] = ['product_id' => '', 'quantity' => 1];
        }
    }

    public function addProductRow(): void
    {
        $this->products[] = ['product_id' => '', 'quantity' => 1];
    }

    public function removeProductRow(int $index): void
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }

    public function save(StoreUploadedImageAsWebp $storeUploadedImageAsWebp): void
    {
        $this->products = array_values(array_filter(
            $this->products,
            fn (array $row) => filled($row['product_id'] ?? null),
        ));

        $validated = $this->validate($this->packageRules($this->package->id));

        $this->package->update([
            ...collect($validated)->except(['image', 'products'])->toArray(),
        ]);

        if ($this->image) {
            $this->package->update([
                'image_path' => $storeUploadedImageAsWebp($this->image, 'packages', $this->package->slug),
            ]);
        }

        $this->package->products()->sync(
            collect($validated['products'])->mapWithKeys(
                fn (array $row) => [$row['product_id'] => ['quantity' => $row['quantity']]],
            ),
        );

        Flux::toast(variant: 'success', text: __('Package updated.'));

        $this->redirect(route('admin.packages.index'), navigate: true);
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:heading size="xl">{{ __('Edit Package') }}</flux:heading>

    <form wire:submit="save" class="mt-6">
        @include('pages::admin.packages._form')

        <div class="mt-6 flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.packages.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
