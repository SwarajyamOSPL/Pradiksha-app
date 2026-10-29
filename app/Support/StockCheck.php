<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;

class StockCheck
{
    /**
     * Which of the wanted products are short of stock.
     *
     * @param  iterable<array{product: Product, quantity: int}>  $lines
     * @return Collection<int, array{product: Product, requested: int, available: int}>
     */
    public static function shortages(iterable $lines): Collection
    {
        return collect($lines)
            ->reject(fn (array $line) => $line['product']->hasStockFor($line['quantity']))
            ->map(fn (array $line) => [
                'product' => $line['product'],
                'requested' => $line['quantity'],
                'available' => max($line['product']->stock, 0),
            ])
            ->values();
    }

    /**
     * Shortages for an existing order, checked against the stock levels right now.
     *
     * @return Collection<int, array{product: Product, requested: int, available: int}>
     */
    public static function forOrder(Order $order): Collection
    {
        $order->loadMissing('items');

        $products = Product::query()->active()->whereIn('id', $order->items->pluck('product_id'))->get()->keyBy('id');

        return self::shortages($order->items->map(function ($item) use ($products): array {
            // A product that was deleted or deactivated since the order was started counts as unavailable.
            $product = $products->get($item->product_id) ?? tap(new Product, fn (Product $missing) => $missing->forceFill([
                'name' => $item->product_name,
                'stock' => 0,
            ]));

            return ['product' => $product, 'quantity' => $item->quantity];
        }));
    }

    /**
     * A customer-friendly explanation of what is short, one sentence per product.
     *
     * @param  Collection<int, array{product: Product, requested: int, available: int}>  $shortages
     */
    public static function message(Collection $shortages): string
    {
        return $shortages
            ->map(fn (array $shortage) => $shortage['available'] <= 0
                ? __(':name is out of stock.', ['name' => $shortage['product']->name])
                : __('Only :available of :name are available (you need :requested).', [
                    'name' => $shortage['product']->name,
                    'available' => $shortage['available'],
                    'requested' => $shortage['requested'],
                ]))
            ->join(' ');
    }
}
