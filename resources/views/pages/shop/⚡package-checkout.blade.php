<?php

use App\Actions\PlaceOrder;
use App\Concerns\PaysWithRazorpay;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Support\OrderTotals;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Checkout')] class extends Component
{
    use PaysWithRazorpay;

    public Package $package;

    public ?string $notes = null;

    public function mount(Package $package): void
    {
        abort_unless($package->is_active, 404);

        $this->package = $package->load('products');
    }

    public function pay(PlaceOrder $placeOrder): void
    {
        abort_unless($this->package->is_active, 404);

        if (! $this->hasPayableAmount($this->totals()->total)) {
            return;
        }

        $order = $this->orderId
            ? Order::query()->where('user_id', Auth::id())->find($this->orderId)
            : null;

        // Start a fresh order if there is none yet, or if the package price changed since the last attempt.
        if (! $order || $order->isPaid() || (float) $order->total_amount !== $this->totals()->total) {
            $order = $placeOrder->forPackage(Auth::user(), $this->package, $this->notes);
        } else {
            $order->update(['notes' => $this->notes]);
        }

        $this->launchPayment($order);
    }

    private function totals(): OrderTotals
    {
        $subtotal = $this->package->products->sum(
            fn (Product $product) => (float) ($product->price ?? 0) * $product->pivot->quantity,
        );

        $breakdown = Package::priceBreakdown($subtotal, $this->package->discount_type, (float) $this->package->discount_value);

        return OrderTotals::calculate($breakdown['subtotal'], $breakdown['discount']);
    }

    public function with(): array
    {
        return ['totals' => $this->totals()];
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
                <flux:text class="font-medium">₹{{ number_format((float) ($product->price ?? 0) * $product->pivot->quantity, 2) }}</flux:text>
            </div>
        @endforeach
    </div>

    @include('pages::shop._order-summary', ['totals' => $totals])

    <flux:textarea wire:model="notes" :label="__('Order notes (optional)')" class="mt-6" rows="3" />

    <div class="mt-6 flex items-center justify-end gap-3">
        <flux:text size="sm" class="inline-flex items-center gap-1">
            <flux:icon.lock-closed class="size-4" /> {{ __('Secure payment by Razorpay') }}
        </flux:text>
        <flux:button variant="primary" icon="credit-card" wire:click="pay" wire:loading.attr="disabled" wire:target="pay">
            {{ __('Pay ₹:amount', ['amount' => number_format($totals->total, 2)]) }}
        </flux:button>
    </div>

    @include('pages::shop._razorpay-script')
</div>
