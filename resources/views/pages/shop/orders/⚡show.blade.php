<?php

use App\Concerns\PaysWithRazorpay;
use App\Models\Order;
use App\Support\OrderTotals;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order Details')] class extends Component
{
    use PaysWithRazorpay;

    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $this->order = $order->load('items', 'package');
        $this->orderId = $order->id;
    }

    public function pay(): void
    {
        $order = Order::query()->where('user_id', Auth::id())->findOrFail($this->order->id);

        $this->launchPayment($order);
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

<div class="mx-auto w-full max-w-2xl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Order #:id', ['id' => $order->id]) }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500">{{ $order->created_at->format('d M Y, h:i A') }}</flux:text>
        </div>

        @if ($order->isPaid() && $order->invoice_number)
            <flux:button icon="arrow-down-tray" :href="route('orders.invoice', $order)">
                {{ __('Download invoice') }}
            </flux:button>
        @endif
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <flux:badge :color="$order->status->color()">{{ __('Status') }}: {{ $order->status->label() }}</flux:badge>
        <flux:badge :color="$order->isPaid() ? 'green' : 'amber'">{{ __('Payment') }}: {{ $order->payment_status->label() }}</flux:badge>
        @if ($order->package)
            <flux:badge>{{ __('Package') }}: {{ $order->package->name }}</flux:badge>
        @endif
        @if ($order->invoice_number)
            <flux:badge>{{ __('Invoice') }}: {{ $order->invoice_number }}</flux:badge>
        @endif
    </div>

    @include('pages::shop._order-tracker', ['order' => $order])

    @include('pages::shop._shipping-status', ['order' => $order])

    @unless ($order->isPaid())
        <flux:callout icon="clock" color="amber" class="mt-6">
            <flux:callout.heading>{{ __('Payment pending') }}</flux:callout.heading>
            <flux:callout.text>{{ __('This order is not confirmed until the payment is completed.') }}</flux:callout.text>
            <x-slot name="actions">
                <flux:button variant="primary" icon="credit-card" wire:click="pay" wire:loading.attr="disabled" wire:target="pay">
                    {{ __('Pay ₹:amount now', ['amount' => number_format((float) $order->total_amount, 2)]) }}
                </flux:button>
            </x-slot>
        </flux:callout>
    @endunless

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

    <div class="mt-6">
        <flux:button variant="ghost" :href="route('orders.index')" wire:navigate>{{ __('Back to orders') }}</flux:button>
    </div>

    @unless ($order->isPaid())
        @include('pages::shop._razorpay-script')
    @endunless
</div>
