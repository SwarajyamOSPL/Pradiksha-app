<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Franchise Holders')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function with(): array
    {
        return [
            'franchiseHolders' => Auth::user()->franchiseHolders()
                ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('business_name', 'like', "%{$this->search}%")))
                ->withCount('orders')
                ->orderBy('name')
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('My Franchise Holders') }}</flux:heading>

    <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search name or business...')" icon="magnifying-glass" class="mt-6 max-w-sm" />

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Business') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Orders') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($franchiseHolders as $holder)
                <flux:table.row wire:key="sales-holder-{{ $holder->id }}">
                    <flux:table.cell>
                        {{ $holder->name }}
                        <flux:text class="text-zinc-500">{{ $holder->email }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $holder->business_name }}</flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $holder->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $holder->orders_count }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" :href="route('sales.franchise-holders.show', $holder)" wire:navigate>
                            {{ __('View') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __("You don't have any franchise holders assigned yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $franchiseHolders->links() }}
    </div>
</section>
