<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order Details')] class extends Component
{
    public Order $order;

    public string $status = '';

    public string $paymentStatus = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load('items', 'package', 'user');
        $this->status = $order->status->value;
        $this->paymentStatus = $order->payment_status->value;
    }

    public function updateOrder(): void
    {
        $this->order->update([
            'status' => OrderStatus::from($this->status),
            'payment_status' => PaymentStatus::from($this->paymentStatus),
        ]);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }
}; ?>

<section class="w-full max-w-2xl">
    <flux:button variant="ghost" :href="route('admin.orders.index')" wire:navigate icon="arrow-left">
        {{ __('Back to orders') }}
    </flux:button>

    <div class="mt-4 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Order #:id', ['id' => $order->id]) }}</flux:heading>
        <flux:text class="text-zinc-500">{{ $order->created_at->format('d M Y, h:i A') }}</flux:text>
    </div>

    <flux:text class="mt-1">
        <flux:link :href="route('admin.franchise-holders.show', $order->user)" wire:navigate>{{ $order->user->name }}</flux:link>
        &middot; {{ $order->user->email }}
    </flux:text>

    @if ($order->package)
        <flux:badge class="mt-2">{{ __('Package') }}: {{ $order->package->name }}</flux:badge>
    @endif

    <div class="mt-6 flex flex-col gap-3">
        @foreach ($order->items as $item)
            <div class="flex items-center justify-between border-b border-zinc-200 pb-2 dark:border-zinc-800">
                <flux:text>{{ $item->product_name }} &times;{{ $item->quantity }}</flux:text>
                <flux:text class="font-medium">₹{{ number_format((float) $item->line_total, 2) }}</flux:text>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex items-center justify-between">
        <flux:heading size="lg">{{ __('Total') }}</flux:heading>
        <flux:heading size="lg">₹{{ number_format((float) $order->total_amount, 2) }}</flux:heading>
    </div>

    @if ($order->notes)
        <flux:callout class="mt-6">
            <flux:callout.heading>{{ __('Notes') }}</flux:callout.heading>
            <flux:callout.text>{{ $order->notes }}</flux:callout.text>
        </flux:callout>
    @endif

    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        <flux:select wire:model="status" :label="__('Order status')">
            @foreach (OrderStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="paymentStatus" :label="__('Payment status')">
            @foreach (PaymentStatus::cases() as $case)
                <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:button variant="primary" class="mt-4" wire:click="updateOrder">{{ __('Update Order') }}</flux:button>
</section>
