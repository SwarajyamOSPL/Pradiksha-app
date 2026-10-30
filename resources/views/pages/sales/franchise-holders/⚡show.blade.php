<?php

use App\Concerns\ShowsFranchiseOrderHistory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Franchise Holder')] class extends Component
{
    use ShowsFranchiseOrderHistory, WithPagination;

    public User $holder;

    public function mount(User $user): void
    {
        abort_unless($user->sales_manager_id === Auth::id(), 403);

        $this->holder = $user;
    }

    public function with(): array
    {
        return ['history' => $this->franchiseHistory($this->holder)];
    }
}; ?>

<section class="w-full max-w-5xl">
    <flux:button variant="ghost" :href="route('sales.franchise-holders.index')" wire:navigate icon="arrow-left">
        {{ __('Back') }}
    </flux:button>

    <div class="mt-4 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $holder->name }}</flux:heading>
            <flux:text class="text-zinc-500">{{ $holder->email }}</flux:text>
        </div>
        <flux:badge>{{ $holder->status->label() }}</flux:badge>
    </div>

    <flux:card class="mt-6">
        <flux:subheading>{{ __('Business') }}</flux:subheading>
        <flux:text>{{ $holder->business_name }}</flux:text>
        <flux:text>{{ $holder->phone }}</flux:text>
        <flux:text>{{ $holder->address }}, {{ $holder->city }}, {{ $holder->state }} {{ $holder->pincode }}</flux:text>
    </flux:card>

    <x-franchise-order-history :history="$history" order-route="sales.orders.show" class="mt-8" />
</section>
