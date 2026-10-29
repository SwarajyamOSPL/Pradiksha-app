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
     * When filled in, the package sells at exactly this price instead of the total of its products.
     */
    public ?string $fixed_price = null;

    /**
     * @return array{subtotal: float, discount: float, price: float, unpriced: array<int, string>, fixed: bool}
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

        $fixed = filled($this->fixed_price) && is_numeric($this->fixed_price);

        $breakdown = $fixed
            ? ['subtotal' => round((float) $this->fixed_price, 2), 'discount' => 0.0, 'price' => round((float) $this->fixed_price, 2)]
            : Package::priceBreakdown(
                $subtotal,
                DiscountType::tryFrom((string) $this->discount_type),
                (float) $this->discount_value,
            );

        return [
            ...$breakdown,
            'unpriced' => $fixed ? [] : array_values(array_unique($unpriced)),
            'fixed' => $fixed,
        ];
    }

    /**
     * Package attributes for the chosen pricing (fixed price, or discount), ready to store.
     *
     * @return array{discount_type: DiscountType|null, discount_value: float, fixed_price: float|null}
     */
    protected function discountAttributes(): array
    {
        $type = DiscountType::tryFrom((string) $this->discount_type);
        $fixed = filled($this->fixed_price) && is_numeric($this->fixed_price);

        return [
            'discount_type' => $fixed ? null : $type,
            'discount_value' => ! $fixed && $type ? (float) $this->discount_value : 0.0,
            'fixed_price' => $fixed ? round((float) $this->fixed_price, 2) : null,
        ];
    }
}
