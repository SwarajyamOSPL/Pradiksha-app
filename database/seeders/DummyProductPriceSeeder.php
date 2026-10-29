<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Fills in placeholder franchise prices (in rupees) for products that have no price yet,
 * so the storefront, packages and checkout can be demonstrated. Products that already
 * have a price are never touched. Replace these with real prices from the admin panel.
 */
class DummyProductPriceSeeder extends Seeder
{
    public function run(): void
    {
        Product::query()
            ->whereNull('price')
            ->get()
            ->each(fn (Product $product) => $product->update(['price' => self::priceFor($product)]));
    }

    /**
     * A believable placeholder price based on the pack size and type in the product name.
     */
    public static function priceFor(Product $product): float
    {
        $name = strtolower($product->name);

        return match (true) {
            str_contains($name, '5 ltr') && str_contains($name, 'shampoo') => 2350,
            str_contains($name, '5 ltr') => 3250,
            str_contains($name, 'pack of 4') => 1100,
            str_contains($name, 'pack of 2'), str_contains($name, 'combo') => 640,
            str_contains($name, 'balm') => 95,
            str_contains($name, 'capsule') => 430,
            str_contains($name, 'tablet') => 335,
            str_contains($name, 'prash') => 545,
            str_contains($name, '50ml') => 125,
            str_contains($name, '100ml') => 215,
            str_contains($name, 'shampoo') => 235,
            str_contains($name, 'syrup') => 225,
            str_contains($name, 'oil') => 285,
            str_contains($name, 'powder') => 565,
            str_contains($name, 'supplement') => 485,
            default => 350,
        };
    }
}
