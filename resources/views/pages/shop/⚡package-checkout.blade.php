<?php

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Package;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checkout')] class extends Component
{
    public Package $package;

    public ?string $notes = null;

    public function mount(Package $package): void
    {
        abort_unless($package->is_active, 404);

        $this->package = $package->load('products');
    }

    public function placeOrder(): void
    {
        abort_unless($this->package->is_active, 404);

        $order = Order::create([
            'user_id' => Auth::id(),
            'package_id' => $this->package->id,
            'type' => OrderType::Package,
            'total_amount' => $this->package->price,
            'payment_method' => 'offline',
            'notes' => $this->notes,
        ]);

        foreach ($this->package->products as $product) {
            $unitPrice = (float) ($product->price ?? 0);
            $quantity = $product->pivot->quantity;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'line_total' => $unitPrice * $quantity,
            ]);
        }

        Flux::toast(variant: 'success', text: __('Order placed successfully.'));

        $this->redirectRoute('orders.show', $order, navigate: true);
    }
}; ?>

<div class="mx-auto w-full max-w-2xl">
        <flux:heading size="xl">{{ __('Checkout') }}</flux:heading>
        <flux:subheading>{{ $package->name }}</flux:subheading>

        @include('pages::shop._shipping-details')

        <div class="mt-6 flex flex-col gap-3">
            @foreach ($package->products as $product)
                <div class="flex items-center justify-between border-b border-zinc-200 pb-2 dark:border-zinc-800">
                    <flux:text>{{ $product->name }} &times;{{ $product->pivot->quantity }}</flux:text>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex items-center justify-between">
            <flux:heading size="lg">{{ __('Total') }}</flux:heading>
            <flux:heading size="lg">₹{{ number_format((float) $package->price, 2) }}</flux:heading>
        </div>

        <flux:textarea wire:model="notes" :label="__('Order notes (optional)')" class="mt-6" rows="3" />

        <div class="mt-6 flex justify-end">
            <flux:button variant="primary" wire:click="placeOrder">{{ __('Place Order') }}</flux:button>
        </div>
    </div>
