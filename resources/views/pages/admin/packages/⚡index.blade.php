<?php

use App\Models\Package;
use Flux\Flux;
use Illuminate\Database\QueryException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Packages')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function delete(Package $package): void
    {
        try {
            $package->delete();
            Flux::toast(variant: 'success', text: __('Package deleted.'));
        } catch (QueryException) {
            Flux::toast(variant: 'danger', text: __('This package cannot be deleted because it has existing orders. Deactivate it instead.'));
        }
    }

    public function toggleActive(Package $package): void
    {
        $package->update(['is_active' => ! $package->is_active]);
    }

    public function with(): array
    {
        return [
            'packages' => Package::query()
                ->withCount('products')
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Packages') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.packages.create')" wire:navigate>
            {{ __('Add Package') }}
        </flux:button>
    </div>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search packages...')" icon="magnifying-glass" class="mt-6 max-w-sm" />

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Image') }}</flux:table.column>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Products') }}</flux:table.column>
            <flux:table.column>{{ __('Price') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($packages as $package)
                <flux:table.row wire:key="package-{{ $package->id }}">
                    <flux:table.cell>
                        @if ($package->image_path)
                            <img src="{{ Storage::url($package->image_path) }}" alt="{{ $package->name }}" class="h-10 w-10 rounded object-cover" />
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $package->name }}</flux:table.cell>
                    <flux:table.cell>{{ trans_choice(':count product|:count products', $package->products_count, ['count' => $package->products_count]) }}</flux:table.cell>
                    <flux:table.cell>₹{{ number_format((float) $package->price, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :variant="$package->is_active ? 'solid' : 'outline'" wire:click="toggleActive({{ $package->id }})" class="cursor-pointer">
                            {{ $package->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex items-center gap-2">
                            <flux:button size="sm" variant="ghost" :href="route('admin.packages.edit', $package)" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:modal.trigger name="delete-package-{{ $package->id }}">
                                <flux:button size="sm" variant="danger">{{ __('Delete') }}</flux:button>
                            </flux:modal.trigger>
                        </div>

                        <flux:modal name="delete-package-{{ $package->id }}" class="max-w-md">
                            <div class="space-y-6">
                                <flux:heading size="lg">{{ __('Delete :name?', ['name' => $package->name]) }}</flux:heading>
                                <flux:subheading>{{ __('This cannot be undone.') }}</flux:subheading>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" wire:click="delete({{ $package->id }})">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ __('No packages found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $packages->links() }}
    </div>
</section>
