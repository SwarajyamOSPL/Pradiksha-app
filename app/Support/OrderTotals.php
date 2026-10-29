<?php

namespace App\Support;

final readonly class OrderTotals
{
    public function __construct(
        public float $subtotal,
        public float $discount,
        public float $taxRate,
        public bool $taxInclusive,
        public float $tax,
        public float $total,
    ) {}

    /**
     * Work out the GST and payable total for an order from the configured tax settings.
     */
    public static function calculate(float $subtotal, float $discount = 0.0): self
    {
        $rate = max((float) config('invoice.tax.rate'), 0);
        $inclusive = (bool) config('invoice.tax.prices_include_tax');

        $subtotal = round(max($subtotal, 0), 2);
        $discount = round(min(max($discount, 0), $subtotal), 2);
        $taxable = round($subtotal - $discount, 2);

        if ($rate <= 0) {
            return new self($subtotal, $discount, 0.0, $inclusive, 0.0, $taxable);
        }

        if ($inclusive) {
            $tax = round($taxable - $taxable / (1 + $rate / 100), 2);

            return new self($subtotal, $discount, $rate, true, $tax, $taxable);
        }

        $tax = round($taxable * $rate / 100, 2);

        return new self($subtotal, $discount, $rate, false, $tax, round($taxable + $tax, 2));
    }

    /**
     * @return array{subtotal: float, discount_amount: float, tax_rate: float, tax_inclusive: bool, tax_amount: float, total_amount: float}
     */
    public function toOrderAttributes(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount,
            'tax_rate' => $this->taxRate,
            'tax_inclusive' => $this->taxInclusive,
            'tax_amount' => $this->tax,
            'total_amount' => $this->total,
        ];
    }
}
