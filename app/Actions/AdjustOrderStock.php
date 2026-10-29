<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AdjustOrderStock
{
    /**
     * Take an order's quantities out of stock. Stock never goes below zero: if two customers
     * pay for the last units at the same moment, the second payment is still recorded but
     * the shortfall is logged so the admin can sort it out.
     */
    public function deduct(Order $order): void
    {
        $this->apply($order, -1);
    }

    /**
     * Put an order's quantities back into stock, for example when the order is cancelled.
     */
    public function restock(Order $order): void
    {
        $this->apply($order, 1);
    }

    private function apply(Order $order, int $direction): void
    {
        $order->loadMissing('items');

        DB::transaction(function () use ($order, $direction): void {
            foreach ($order->items as $item) {
                $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();

                if (! $product) {
                    continue;
                }

                $change = $direction * $item->quantity;

                if ($direction < 0 && $product->stock < $item->quantity) {
                    logger()->warning('Order paid for more stock than was available.', [
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'wanted' => $item->quantity,
                        'available' => $product->stock,
                    ]);
                }

                $product->update(['stock' => max($product->stock + $change, 0)]);
            }
        });
    }
}
