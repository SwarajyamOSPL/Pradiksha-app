<?php

use App\Actions\AdjustOrderStock;
use App\Actions\MarkOrderAsPaid;
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

    public string $courierName = '';

    public string $awbNumber = '';

    public string $trackingUrl = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load('items', 'package', 'user');
        $this->status = $order->status->value;
        $this->paymentStatus = $order->payment_status->value;
        $this->courierName = (string) $order->courier_name;
        $this->awbNumber = (string) $order->awb_number;
        $this->trackingUrl = (string) $order->tracking_url;
    }

    /**
     * Save the courier, AWB number and tracking link once the order has been dispatched. The first
     * time they are saved the order is marked as shipped, unless it is already further along.
     */
    public function saveShipping(): void
    {
        if (! $this->order->isPaid()) {
            Flux::toast(variant: 'danger', text: __('Only paid orders can be dispatched.'));

            return;
        }

        $validated = $this->validate([
            'courierName' => ['nullable', 'string', 'max:100'],
            'awbNumber' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9\-_\/ ]+$/'],
            'trackingUrl' => ['nullable', 'url:http,https', 'max:2048'],
        ], attributes: [
            'courierName' => __('courier'),
            'awbNumber' => __('AWB number'),
            'trackingUrl' => __('tracking link'),
        ]);

        $firstDispatch = ! $this->order->isDispatched();

        $this->order->update([
            'courier_name' => filled($validated['courierName']) ? trim($validated['courierName']) : null,
            'awb_number' => trim($validated['awbNumber']),
            'tracking_url' => filled($validated['trackingUrl']) ? trim($validated['trackingUrl']) : null,
            'dispatched_at' => $this->order->dispatched_at ?? now(),
        ]);

        if ($firstDispatch && in_array($this->order->status, [OrderStatus::Confirmed, OrderStatus::Processing], true)) {
            $this->order->update(['status' => OrderStatus::Shipped]);
            $this->status = OrderStatus::Shipped->value;
        }

        Flux::toast(variant: 'success', text: __('Shipping details saved.'));
    }

    public function updateOrder(MarkOrderAsPaid $markOrderAsPaid): void
    {
        $newPaymentStatus = PaymentStatus::from($this->paymentStatus);
        $statusBefore = $this->order->status->value;

        // Marking an unpaid order as paid by hand (e.g. cash or bank transfer) goes through the
        // normal flow so it gets an invoice number, a confirmation email and an admin notification.
        if ($newPaymentStatus === PaymentStatus::Paid && ! $this->order->isPaid()) {
            $this->order = $markOrderAsPaid($this->order, 'manual', 'manual')->load('items', 'package', 'user');
        } else {
            $this->order->update(['payment_status' => $newPaymentStatus]);
        }

        // Only apply the status dropdown if the admin changed it, so a manual payment keeps its "Confirmed" status.
        if ($this->status !== $statusBefore) {
            $newStatus = OrderStatus::from($this->status);

            // A paid order's stock is returned when it is cancelled, and taken out again if the cancellation is undone.
            if ($this->order->isPaid()) {
                if ($newStatus === OrderStatus::Cancelled && $statusBefore !== OrderStatus::Cancelled->value) {
                    app(AdjustOrderStock::class)->restock($this->order);
                } elseif ($statusBefore === OrderStatus::Cancelled->value && $newStatus !== OrderStatus::Cancelled) {
                    app(AdjustOrderStock::class)->deduct($this->order);
                }
            }

            $this->order->update(['status' => $newStatus]);
        }

        $this->status = $this->order->status->value;
        $this->paymentStatus = $this->order->payment_status->value;

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
                <flux:text class="font-medium">{{ $order->itemsAreIncluded() ? __('Included') : '₹'.number_format((float) $item->line_total, 2) }}</flux:text>
            </div>
        @endforeach
    </div>

    <div class="mt-4 flex flex-col gap-1 text-sm">
        @if ((float) $order->discount_amount > 0 || (float) $order->tax_amount > 0)
            <div class="flex items-center justify-between">
                <flux:text>{{ __('Subtotal') }}</flux:text>
                <flux:text>₹{{ number_format((float) $order->subtotal, 2) }}</flux:text>
            </div>
        @endif
        @if ((float) $order->discount_amount > 0)
            <div class="flex items-center justify-between">
                <flux:text>{{ __('Discount') }}</flux:text>
                <flux:text>-₹{{ number_format((float) $order->discount_amount, 2) }}</flux:text>
            </div>
        @endif
        @if ((float) $order->tax_amount > 0)
            <div class="flex items-center justify-between">
                <flux:text>{{ config('invoice.tax.label') }} ({{ rtrim(rtrim(number_format((float) $order->tax_rate, 2), '0'), '.') }}%{{ $order->tax_inclusive ? ', '.__('included') : '' }})</flux:text>
                <flux:text>₹{{ number_format((float) $order->tax_amount, 2) }}</flux:text>
            </div>
        @endif
        <div class="mt-2 flex items-center justify-between">
            <flux:heading size="lg">{{ __('Total') }}</flux:heading>
            <flux:heading size="lg">₹{{ number_format((float) $order->total_amount, 2) }}</flux:heading>
        </div>
    </div>

    <flux:callout class="mt-6" :icon="$order->isPaid() ? 'check-circle' : 'clock'" :color="$order->isPaid() ? 'green' : 'amber'">
        <flux:callout.heading>
            {{ $order->isPaid() ? __('Paid on :date', ['date' => $order->paid_at?->format('d M Y, h:i A')]) : __('Awaiting payment') }}
        </flux:callout.heading>
        @if ($order->isPaid())
            <flux:callout.text>
                {{ __('Invoice') }}: {{ $order->invoice_number }}
                &middot; {{ $order->payment_method === 'razorpay' ? __('Razorpay') : __('Marked as paid manually') }}
                @if ($order->razorpay_payment_id)
                    &middot; {{ __('Payment ID') }}: {{ $order->razorpay_payment_id }}
                @endif
            </flux:callout.text>
            <x-slot name="actions">
                <flux:button icon="arrow-down-tray" :href="route('orders.invoice', $order)">{{ __('Download invoice') }}</flux:button>
            </x-slot>
        @else
            <flux:callout.text>{{ __('The customer has not completed the payment, so this order is not counted as placed.') }}</flux:callout.text>
        @endif
    </flux:callout>

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

    @if ($order->isPaid())
        <flux:card class="mt-10">
            <flux:heading size="lg">{{ __('Shipping details') }}</flux:heading>
            <flux:text class="mt-1">
                {{ __('Once the order has been dispatched, save the AWB number and tracking link here. The franchise holder sees them on their order, and a first save marks the order as shipped.') }}
            </flux:text>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="courierName" :label="__('Courier (optional)')" type="text" placeholder="{{ __('e.g. Delhivery, DTDC') }}" />
                <flux:input wire:model="awbNumber" :label="__('AWB number')" type="text" required />
            </div>

            <flux:input wire:model="trackingUrl" :label="__('Tracking link (optional)')" type="url" class="mt-4" placeholder="https://" description="{{ __('The page where this AWB number can be tracked.') }}" />

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <flux:button variant="primary" wire:click="saveShipping">{{ __('Save shipping details') }}</flux:button>
                @if ($order->dispatched_at)
                    <flux:text size="sm">{{ __('Dispatched on :date', ['date' => $order->dispatched_at->format('d M Y, h:i A')]) }}</flux:text>
                @endif
            </div>
        </flux:card>
    @endif
</section>
