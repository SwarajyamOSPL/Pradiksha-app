<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class MarkOrderAsPaid
{
    /**
     * Record a verified payment against an order. Safe to call more than once:
     * only the first call changes the order and fires the OrderPaid event.
     */
    public function __invoke(Order $order, string $razorpayPaymentId, string $paymentMethod = 'razorpay'): Order
    {
        $newlyPaid = DB::transaction(function () use ($order, $razorpayPaymentId, $paymentMethod): bool {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                return false;
            }

            $locked->update([
                'payment_status' => PaymentStatus::Paid,
                'status' => OrderStatus::Confirmed,
                'payment_method' => $paymentMethod,
                'razorpay_payment_id' => $paymentMethod === 'razorpay' ? $razorpayPaymentId : null,
                'paid_at' => now(),
                'invoice_number' => sprintf('%s-%s-%06d', config('invoice.prefix'), now()->format('Y'), $locked->id),
            ]);

            app(AdjustOrderStock::class)->deduct($locked);

            return true;
        });

        $order->refresh();

        if ($newlyPaid) {
            OrderPaid::dispatch($order);
        }

        return $order;
    }
}
