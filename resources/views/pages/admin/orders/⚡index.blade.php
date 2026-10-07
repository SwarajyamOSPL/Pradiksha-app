<?php

use App\Models\Order;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Orders')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $status = '';

    public function with(): array
    {
        return [
            'orders' => Order::query()
                ->with('user')
                ->when($this->status, fn ($query) => $query->where('status', $this->status))
                ->latest()
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Orders') }}</flux:heading>

    <flux:select wire:model.live="status" class="mt-6 max-w-xs">
        <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
        @foreach (\App\Enums\OrderStatus::cases() as $case)
            <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
        @endforeach
    </flux:select>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Order') }}</flux:table.column>
            <flux:table.column>{{ __('Franchise Holder') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Payment') }}</flux:table.column>
            <flux:table.column>{{ __('Total') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($orders as $order)
                <flux:table.row wire:key="admin-order-{{ $order->id }}">
                    <flux:table.cell>#{{ $order->id }}</flux:table.cell>
                    <flux:table.cell>{{ $order->user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $order->created_at->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $order->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $order->payment_status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" :href="route('admin.orders.show', $order)" wire:navigate>
                            {{ __('View') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ __('No orders found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $orders->links() }}
    </div>
</section>
