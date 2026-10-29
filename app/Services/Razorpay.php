<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class Razorpay
{
    public function isConfigured(): bool
    {
        return filled(config('services.razorpay.key_id')) && filled(config('services.razorpay.key_secret'));
    }

    public function keyId(): string
    {
        return (string) config('services.razorpay.key_id');
    }

    /**
     * Create a Razorpay order for the payable amount and return its id.
     */
    public function createOrder(Order $order): string
    {
        $response = Http::withBasicAuth($this->keyId(), (string) config('services.razorpay.key_secret'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post(rtrim((string) config('services.razorpay.base_url'), '/').'/orders', [
                'amount' => $this->toPaise($order->total_amount),
                'currency' => 'INR',
                'receipt' => 'order_'.$order->id,
                'notes' => ['order_id' => (string) $order->id],
            ]);

        if ($response->failed() || blank($response->json('id'))) {
            throw new RuntimeException('Razorpay order creation failed: '.$response->body());
        }

        return (string) $response->json('id');
    }

    /**
     * Check the signature Razorpay returns to the browser after a successful payment.
     */
    public function verifySignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature): bool
    {
        $expected = hash_hmac(
            'sha256',
            $razorpayOrderId.'|'.$razorpayPaymentId,
            (string) config('services.razorpay.key_secret'),
        );

        return hash_equals($expected, $signature);
    }

    public function toPaise(float|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
