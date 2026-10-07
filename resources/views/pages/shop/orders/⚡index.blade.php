<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My Orders')] class extends Component
{
    use WithPagination;

    public function with(): array
    {
        return [
            'orders' => Auth::user()->orders()->latest()->paginate(10),
        ];
    }
}; ?>

<div class="w-full">
        <flux:heading size="xl">{{ __('My Orders') }}</flux:heading>

        <flux:table class="mt-6">
            <flux:table.columns>
                <flux:table.column>{{ __('Order') }}</flux:table.column>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Payment') }}</flux:table.column>
                <flux:table.column>{{ __('Total') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($orders as $order)
                    <flux:table.row wire:key="order-{{ $order->id }}">
                        <flux:table.cell>#{{ $order->id }}</flux:table.cell>
                        <flux:table.cell>{{ $order->created_at->format('d M Y') }}</flux:table.cell>
                        <flux:table.cell class="capitalize">{{ $order->type->value }}</flux:table.cell>
                        <flux:table.cell><flux:badge>{{ $order->status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell><flux:badge>{{ $order->payment_status->label() }}</flux:badge></flux:table.cell>
                        <flux:table.cell>₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:button size="sm" variant="ghost" :href="route('orders.show', $order)" wire:navigate>
                                {{ __('View') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7">{{ __("You haven't placed any orders yet.") }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-6">
            {{ $orders->links() }}
        </div>
    </div>
