<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->randomFloat(2, 500, 5000);

        return [
            'user_id' => User::factory(),
            'package_id' => null,
            'type' => OrderType::Custom,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'payment_method' => null,
            'subtotal' => $total,
            'total_amount' => $total,
        ];
    }

    /**
     * Indicate that the order's payment was verified, so it counts as placed and has an invoice.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'payment_method' => 'razorpay',
            'razorpay_order_id' => 'order_'.fake()->unique()->bothify('??????????'),
            'razorpay_payment_id' => 'pay_'.fake()->unique()->bothify('??????????'),
            'paid_at' => now(),
            'invoice_number' => 'INV-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
        ]);
    }
}
