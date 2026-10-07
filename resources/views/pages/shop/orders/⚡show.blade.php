<?php

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order Details')] class extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $this->order = $order->load('items', 'package');
    }
}; ?>

<div class="mx-auto w-full max-w-2xl">
        <flux:heading size="xl">{{ __('Order #:id', ['id' => $order->id]) }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500">{{ $order->created_at->format('d M Y, h:i A') }}</flux:text>

        <div class="mt-4 flex flex-wrap gap-2">
            <flux:badge>{{ __('Status') }}: {{ $order->status->label() }}</flux:badge>
            <flux:badge>{{ __('Payment') }}: {{ $order->payment_status->label() }}</flux:badge>
            @if ($order->package)
                <flux:badge>{{ __('Package') }}: {{ $order->package->name }}</flux:badge>
            @endif
        </div>

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

        <div class="mt-6">
            <flux:button variant="ghost" :href="route('orders.index')" wire:navigate>{{ __('Back to orders') }}</flux:button>
        </div>
    </div>
