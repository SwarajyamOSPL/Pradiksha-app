<?php

namespace App\Concerns;

use App\Enums\DiscountType;
use App\Models\Package;

/**
 * Live price calculation for the admin package form: the package price is the total of
 * its products (price x quantity) minus an optional percentage or fixed discount.
 */
trait CalculatesPackagePricing
{
    public ?string $discount_type = null;

    public ?string $discount_value = '0';

    /**
     * @return array{subtotal: float, discount: float, price: float, unpriced: array<int, string>}
     */
    public function pricingSummary(): array
    {
        $subtotal = 0.0;
        $unpriced = [];

        foreach ($this->products as $row) {
            $product = $this->availableProducts->firstWhere('id', (int) ($row['product_id'] ?? 0));

            if (! $product) {
                continue;
            }

            $price = (float) ($product->price ?? 0);

            if ($price <= 0) {
                $unpriced[] = $product->name;
            }

            $subtotal += $price * max((int) ($row['quantity'] ?? 0), 0);
        }

        return [
            ...Package::priceBreakdown(
                $subtotal,
                DiscountType::tryFrom((string) $this->discount_type),
                (float) $this->discount_value,
            ),
            'unpriced' => array_values(array_unique($unpriced)),
        ];
    }

    /**
     * Package attributes for the chosen discount, ready to store.
     *
     * @return array{discount_type: DiscountType|null, discount_value: float}
     */
    protected function discountAttributes(): array
    {
        $type = DiscountType::tryFrom((string) $this->discount_type);

        return [
            'discount_type' => $type,
            'discount_value' => $type ? (float) $this->discount_value : 0.0,
        ];
    }
}
