<?php

namespace App\Actions;

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use App\Support\Cart;
use App\Support\OrderTotals;
use Illuminate\Support\Facades\DB;

class PlaceOrder
{
    /**
     * Create an unpaid order for a package at its current price.
     */
    public function forPackage(User $user, Package $package, ?string $notes = null): Order
    {
        $package->load('products');

        $subtotal = $package->products->sum(
            fn (Product $product) => (float) ($product->price ?? 0) * $product->pivot->quantity,
        );

        $breakdown = Package::priceBreakdown($subtotal, $package->discount_type, (float) $package->discount_value);

        return DB::transaction(function () use ($user, $package, $notes, $breakdown) {
            $order = Order::create([
                ...OrderTotals::calculate($breakdown['subtotal'], $breakdown['discount'])->toOrderAttributes(),
                'user_id' => $user->id,
                'package_id' => $package->id,
                'type' => OrderType::Package,
                'payment_method' => 'razorpay',
                'notes' => $notes,
            ]);

            foreach ($package->products as $product) {
                $this->addItem($order, $product, $product->pivot->quantity);
            }

            return $order;
        });
    }

    /**
     * Create an unpaid order from the items in the cart.
     */
    public function forCart(User $user, Cart $cart, ?string $notes = null): Order
    {
        $items = $cart->items();

        return DB::transaction(function () use ($user, $items, $notes) {
            $order = Order::create([
                ...OrderTotals::calculate($items->sum(
                    fn (array $item) => (float) ($item['product']->price ?? 0) * $item['quantity'],
                ))->toOrderAttributes(),
                'user_id' => $user->id,
                'package_id' => null,
                'type' => OrderType::Custom,
                'payment_method' => 'razorpay',
                'notes' => $notes,
            ]);

            foreach ($items as $item) {
                $this->addItem($order, $item['product'], $item['quantity']);
            }

            return $order;
        });
    }

    private function addItem(Order $order, Product $product, int $quantity): void
    {
        $unitPrice = (float) ($product->price ?? 0);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => round($unitPrice * $quantity, 2),
        ]);
    }
}
