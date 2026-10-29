<?php

use App\Models\Order;
use App\Support\OrderTotals;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order Details')] class extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $order->load('items', 'package', 'user');

        abort_unless($order->user->sales_manager_id === Auth::id(), 403);
        abort_unless($order->isPaid(), 404);

        $this->order = $order;
    }

    public function with(): array
    {
        return [
            'totals' => new OrderTotals(
                (float) $this->order->subtotal,
                (float) $this->order->discount_amount,
                (float) $this->order->tax_rate,
                (bool) $this->order->tax_inclusive,
                (float) $this->order->tax_amount,
                (float) $this->order->total_amount,
            ),
        ];
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:button variant="ghost" :href="route('sales.dashboard')" wire:navigate icon="arrow-left">
        {{ __('Back to dashboard') }}
    </flux:button>

    <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Order #:id', ['id' => $order->id]) }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ ($order->paid_at ?? $order->created_at)->format('d M Y, h:i A') }}</flux:text>
        </div>
        <flux:badge :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
    </div>

    <flux:card class="mt-6">
        <flux:subheading>{{ __('Franchise') }}</flux:subheading>
        <flux:text>{{ $order->user->business_name ?: $order->user->name }}</flux:text>
        <flux:text>{{ $order->user->name }} &middot; {{ $order->user->phone }}</flux:text>
        <flux:text>{{ $order->user->address }}, {{ $order->user->city }}, {{ $order->user->state }} {{ $order->user->pincode }}</flux:text>
    </flux:card>

    @if ($order->package)
        <flux:badge class="mt-4">{{ __('Package') }}: {{ $order->package->name }}</flux:badge>
    @endif

    @include('pages::shop._shipping-status', ['order' => $order])

    <div class="mt-6 flex flex-col gap-3">
        @foreach ($order->items as $item)
            <div class="flex items-center justify-between border-b border-zinc-200 pb-2 dark:border-zinc-800">
                <flux:text>{{ $item->product_name }} &times;{{ $item->quantity }}</flux:text>
                <flux:text class="font-medium">{{ $order->itemsAreIncluded() ? __('Included') : '₹'.number_format((float) $item->line_total, 2) }}</flux:text>
            </div>
        @endforeach
    </div>

    @include('pages::shop._order-summary', ['totals' => $totals])

    @if ($order->notes)
        <flux:callout class="mt-6">
            <flux:callout.heading>{{ __('Notes') }}</flux:callout.heading>
            <flux:callout.text>{{ $order->notes }}</flux:callout.text>
        </flux:callout>
    @endif
</section>
