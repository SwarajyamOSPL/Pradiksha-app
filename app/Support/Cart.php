<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    private const SESSION_KEY = 'cart';

    /**
     * The most of any one product that can be in the basket.
     */
    public const MAX_QUANTITY = 999;

    /**
     * @return array<int, int> Product ID => quantity.
     */
    public function raw(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $items = $this->raw();
        $items[$productId] = min(self::MAX_QUANTITY, max(1, ($items[$productId] ?? 0) + $quantity));

        session([self::SESSION_KEY => $items]);
    }

    public function update(int $productId, int $quantity): void
    {
        $items = $this->raw();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = min(self::MAX_QUANTITY, $quantity);
        }

        session([self::SESSION_KEY => $items]);
    }

    public function remove(int $productId): void
    {
        $this->update($productId, 0);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    /**
     * The products in the basket. Products that were deleted or deactivated since they
     * were added are dropped from the basket so they can never be ordered.
     *
     * @return Collection<int, array{product: Product, quantity: int}>
     */
    public function items(): Collection
    {
        $raw = $this->raw();

        if (empty($raw)) {
            return collect();
        }

        $products = Product::query()->active()->whereIn('id', array_keys($raw))->get();

        if ($products->count() !== count($raw)) {
            $raw = array_intersect_key($raw, $products->pluck('id', 'id')->all());

            session([self::SESSION_KEY => $raw]);
        }

        return $products
            ->map(fn (Product $product) => [
                'product' => $product,
                'quantity' => $raw[$product->id],
            ])
            ->values();
    }

    public function total(): float
    {
        return $this->items()->sum(fn (array $item) => (float) ($item['product']->price ?? 0) * $item['quantity']);
    }

    /**
     * Whether any product in the basket has no price yet, which makes it impossible to pay for.
     */
    public function hasUnpricedItems(): bool
    {
        return $this->items()->contains(fn (array $item) => (float) ($item['product']->price ?? 0) <= 0);
    }
}
