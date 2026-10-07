<?php

use App\Actions\StoreUploadedImageAsWebp;
use App\Concerns\PackageValidationRules;
use App\Models\Package;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Create Package')] class extends Component
{
    use PackageValidationRules, WithFileUploads;

    public string $name = '';

    public string $slug = '';

    public ?string $description = null;

    public ?string $price = null;

    public bool $is_active = true;

    public mixed $image = null;

    public ?string $currentImagePath = null;

    /** @var array<int, array{product_id: string, quantity: int}> */
    public array $products = [
        ['product_id' => '', 'quantity' => 1],
    ];

    public Collection $availableProducts;

    private bool $slugManuallyEdited = false;

    public function mount(): void
    {
        $this->availableProducts = Product::query()->orderBy('name')->get();
    }

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

        $validated = $this->validate($this->packageRules());

        $package = Package::create([
            ...collect($validated)->except(['image', 'products'])->toArray(),
        ]);

        if ($this->image) {
            $package->update([
                'image_path' => $storeUploadedImageAsWebp($this->image, 'packages', $package->slug),
            ]);
        }

        $package->products()->sync(
            collect($validated['products'])->mapWithKeys(
                fn (array $row) => [$row['product_id'] => ['quantity' => $row['quantity']]],
            ),
        );

        Flux::toast(variant: 'success', text: __('Package created.'));

        $this->redirect(route('admin.packages.index'), navigate: true);
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:heading size="xl">{{ __('Create Package') }}</flux:heading>

    <form wire:submit="save" class="mt-6">
        @include('pages::admin.packages._form')

        <div class="mt-6 flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.packages.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
