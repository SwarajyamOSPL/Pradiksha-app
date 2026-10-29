<?php

use App\Actions\PlaceOrder;
use App\Concerns\PaysWithRazorpay;
use App\Models\Order;
use App\Support\Cart;
use App\Support\OrderTotals;
use App\Support\StockCheck;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checkout')] class extends Component
{
    use PaysWithRazorpay;

    public ?string $notes = null;

    public function mount(Cart $cart): void
    {
        if ($cart->items()->isEmpty()) {
            $this->redirectRoute('cart.index', navigate: true);
        }
    }

    public function pay(Cart $cart, PlaceOrder $placeOrder): void
    {
        abort_if($cart->items()->isEmpty(), 404);

        if (! $this->hasEnoughStock(StockCheck::shortages($cart->items()))) {
            return;
        }

        if (! $this->hasPayableAmount(OrderTotals::calculate($cart->total())->total)) {
            return;
        }

        $order = $this->orderId
            ? Order::query()->where('user_id', Auth::id())->find($this->orderId)
            : null;

        // Start a fresh order if there is none yet, or if the basket total changed since the last attempt.
        if (! $order || $order->isPaid() || (float) $order->total_amount !== OrderTotals::calculate($cart->total())->total) {
            $order = $placeOrder->forCart(Auth::user(), $cart, $this->notes);
        } else {
            $order->update(['notes' => $this->notes]);
        }

        $this->launchPayment($order);
    }

    protected function paymentCompleted(Order $order): void
    {
        app(Cart::class)->clear();
    }

    public function with(Cart $cart): array
    {
        return [
            'items' => $cart->items(),
            'totals' => OrderTotals::calculate($cart->total()),
            'shortages' => StockCheck::shortages($cart->items()),
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

    @include('pages::shop._order-summary', ['totals' => $totals])

    @if ($shortages->isNotEmpty())
        <flux:callout icon="exclamation-triangle" color="red" class="mt-6">
            <flux:callout.heading>{{ __('Some items are no longer available in this quantity') }}</flux:callout.heading>
            <flux:callout.text>
                {{ \App\Support\StockCheck::message($shortages) }}
                <flux:link :href="route('cart.index')" wire:navigate>{{ __('Update your basket') }}</flux:link>
            </flux:callout.text>
        </flux:callout>
    @endif

    <flux:textarea wire:model="notes" :label="__('Order notes (optional)')" class="mt-6" rows="3" />

    <div class="mt-6 flex items-center justify-end gap-3">
        <flux:text size="sm" class="inline-flex items-center gap-1">
            <flux:icon.lock-closed class="size-4" /> {{ __('Secure payment by Razorpay') }}
        </flux:text>
        <flux:button variant="primary" icon="credit-card" wire:click="pay" wire:loading.attr="disabled" wire:target="pay" :disabled="$shortages->isNotEmpty()">
            {{ __('Pay ₹:amount', ['amount' => number_format($totals->total, 2)]) }}
        </flux:button>
    </div>

    @include('pages::shop._razorpay-script')
</div>
