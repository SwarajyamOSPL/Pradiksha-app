<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Franchise Holder')] class extends Component
{
    public User $holder;

    public function mount(User $user): void
    {
        abort_unless($user->sales_manager_id === Auth::id(), 403);

        $this->holder = $user;
    }

    public function with(): array
    {
        return [
            'orders' => $this->holder->orders()->latest()->paginate(10),
        ];
    }
}; ?>

<section class="w-full max-w-3xl">
    <flux:button variant="ghost" :href="route('sales.dashboard')" wire:navigate icon="arrow-left">
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

    <flux:heading size="lg" class="mt-8">{{ __('Order History') }}</flux:heading>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Order') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Total') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($orders as $order)
                <flux:table.row wire:key="sales-order-{{ $order->id }}">
                    <flux:table.cell>#{{ $order->id }}</flux:table.cell>
                    <flux:table.cell>{{ $order->created_at->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $order->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __('No orders yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</section>
