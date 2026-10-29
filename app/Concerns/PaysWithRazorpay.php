<?php

namespace App\Concerns;

use App\Actions\MarkOrderAsPaid;
use App\Models\Order;
use App\Services\Razorpay;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Shared Razorpay payment flow for Livewire pages: open the Razorpay window for
 * an unpaid order, then verify the signature it returns before marking it paid.
 */
trait PaysWithRazorpay
{
    /**
     * The unpaid order currently being paid for.
     */
    public ?int $orderId = null;

    /**
     * Refuse to start a payment for an amount below Razorpay's ₹1 minimum, before any order is created.
     */
    protected function hasPayableAmount(float $total): bool
    {
        if ($total >= 1) {
            return true;
        }

        Flux::toast(variant: 'danger', text: __('This order has no payable amount because some prices are not set yet. Please contact us.'));

        return false;
    }

    /**
     * Create the Razorpay order if needed and tell the browser to open the payment window.
     */
    protected function launchPayment(Order $order): void
    {
        $razorpay = app(Razorpay::class);

        if (! $razorpay->isConfigured()) {
            Flux::toast(variant: 'danger', text: __('Online payment is not available right now. Please contact us to complete your order.'));

            return;
        }

        if ($order->isPaid()) {
            $this->redirectRoute('orders.show', $order, navigate: true);

            return;
        }

        if ((float) $order->total_amount < 1) {
            Flux::toast(variant: 'danger', text: __('This order has no payable amount. Please contact us.'));

            return;
        }

        try {
            if (blank($order->razorpay_order_id)) {
                $order->update(['razorpay_order_id' => $razorpay->createOrder($order)]);
            }
        } catch (Throwable $exception) {
            report($exception);
            Flux::toast(variant: 'danger', text: __('We could not start the payment. Please try again.'));

            return;
        }

        $this->orderId = $order->id;

        $this->dispatch('start-razorpay', options: [
            'key' => $razorpay->keyId(),
            'amount' => $razorpay->toPaise($order->total_amount),
            'currency' => 'INR',
            'name' => config('app.name'),
            'description' => __('Order #:id', ['id' => $order->id]),
            'order_id' => $order->razorpay_order_id,
            'prefill' => [
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
                'contact' => Auth::user()->phone,
            ],
            'theme' => ['color' => '#467536'],
        ]);
    }

    /**
     * Called from the browser once Razorpay reports a successful payment.
     *
     * @param  array<string, mixed>  $response
     */
    public function confirmPayment(array $response): void
    {
        $order = Order::query()
            ->where('user_id', Auth::id())
            ->find($this->orderId);

        $paymentId = (string) ($response['razorpay_payment_id'] ?? '');
        $razorpayOrderId = (string) ($response['razorpay_order_id'] ?? '');
        $signature = (string) ($response['razorpay_signature'] ?? '');

        if (
            ! $order
            || blank($order->razorpay_order_id)
            || $razorpayOrderId !== $order->razorpay_order_id
            || ! app(Razorpay::class)->verifySignature($razorpayOrderId, $paymentId, $signature)
        ) {
            Flux::toast(variant: 'danger', text: __('We could not verify your payment. If money was deducted, please contact us with your order number.'));

            return;
        }

        $order = app(MarkOrderAsPaid::class)($order, $paymentId);

        $this->paymentCompleted($order);

        Flux::toast(variant: 'success', text: __('Payment successful. A confirmation email with your invoice is on its way.'));

        $this->redirectRoute('orders.show', $order, navigate: true);
    }

    /**
     * Called from the browser when the customer closes the payment window without paying.
     */
    public function paymentDismissed(): void
    {
        Flux::toast(variant: 'warning', text: __('Payment was not completed. You can try again whenever you are ready.'));
    }

    /**
     * Hook for pages that need to do something once payment succeeds, such as emptying the cart.
     */
    protected function paymentCompleted(Order $order): void {}
}
