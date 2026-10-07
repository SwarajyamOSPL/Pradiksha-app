<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    private const SESSION_KEY = 'cart';

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
        $items[$productId] = max(1, ($items[$productId] ?? 0) + $quantity);

        session([self::SESSION_KEY => $items]);
    }

    public function update(int $productId, int $quantity): void
    {
        $items = $this->raw();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
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
        return array_sum($this->raw());
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int}>
     */
    public function items(): Collection
    {
        $raw = $this->raw();

        if (empty($raw)) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', array_keys($raw))
            ->get()
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
}
