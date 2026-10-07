<?php

use App\Enums\OrderType;
use App\Models\Order;
use App\Support\Cart;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checkout')] class extends Component
{
    public ?string $notes = null;

    public function mount(Cart $cart): void
    {
        if ($cart->items()->isEmpty()) {
            $this->redirectRoute('cart.index', navigate: true);
        }
    }

    public function placeOrder(Cart $cart): void
    {
        $items = $cart->items();

        abort_if($items->isEmpty(), 404);

        $order = Order::create([
            'user_id' => Auth::id(),
            'package_id' => null,
            'type' => OrderType::Custom,
            'total_amount' => $cart->total(),
            'payment_method' => 'offline',
            'notes' => $this->notes,
        ]);

        foreach ($items as $item) {
            $unitPrice = (float) ($item['product']->price ?? 0);

            $order->items()->create([
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'line_total' => $unitPrice * $item['quantity'],
            ]);
        }

        $cart->clear();

        Flux::toast(variant: 'success', text: __('Order placed successfully.'));

        $this->redirectRoute('orders.show', $order, navigate: true);
    }

    public function with(Cart $cart): array
    {
        return [
            'items' => $cart->items(),
            'total' => $cart->total(),
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-2xl">
        <flux:heading size="xl">{{ __('Checkout') }}</flux:heading>

        @include('pages::shop._shipping-details')

        <div class="mt-6 flex flex-col gap-3">
            @foreach ($items as $item)
                <div class="flex items-center justify-between border-b border-zinc-200 pb-2 dark:border-zinc-800">
                    <flux:text>{{ $item['product']->name }} &times;{{ $item['quantity'] }}</flux:text>
                    <flux:text class="font-medium">
                        ₹{{ number_format((float) ($item['product']->price ?? 0) * $item['quantity'], 2) }}
                    </flux:text>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex items-center justify-between">
            <flux:heading size="lg">{{ __('Total') }}</flux:heading>
            <flux:heading size="lg">₹{{ number_format($total, 2) }}</flux:heading>
        </div>

        <flux:textarea wire:model="notes" :label="__('Order notes (optional)')" class="mt-6" rows="3" />

        <div class="mt-6 flex justify-end">
            <flux:button variant="primary" wire:click="placeOrder">{{ __('Place Order') }}</flux:button>
        </div>
    </div>
