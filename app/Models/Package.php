<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Support\StockCheck;
use Database\Factories\PackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $image_path
 * @property float $subtotal
 * @property DiscountType|null $discount_type
 * @property float $discount_value
 * @property float $price
 * @property bool $is_active
 */
#[Fillable(['name', 'slug', 'description', 'image_path', 'subtotal', 'discount_type', 'discount_value', 'price', 'is_active'])]
class Package extends Model
{
    /** @use HasFactory<PackageFactory> */
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
        'subtotal' => 0,
        'discount_value' => 0,
        'price' => 0,
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_type' => DiscountType::class,
            'discount_value' => 'decimal:2',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Work out the subtotal, discount and final price for a set of products.
     *
     * @return array{subtotal: float, discount: float, price: float}
     */
    public static function priceBreakdown(float $subtotal, ?DiscountType $type, float $discountValue): array
    {
        $subtotal = round(max($subtotal, 0), 2);

        $discount = match ($type) {
            DiscountType::Percent => $subtotal * min(max($discountValue, 0), 100) / 100,
            DiscountType::Fixed => max($discountValue, 0),
            default => 0.0,
        };

        $discount = round(min($discount, $subtotal), 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'price' => round($subtotal - $discount, 2),
        ];
    }

    /**
     * Recalculate and store the package subtotal and final price from its products.
     */
    public function refreshPrice(): void
    {
        $subtotal = $this->products()->get()->sum(
            fn (Product $product) => (float) ($product->price ?? 0) * $product->pivot->quantity,
        );

        $breakdown = self::priceBreakdown($subtotal, $this->discount_type, (float) $this->discount_value);

        $this->forceFill([
            'subtotal' => $breakdown['subtotal'],
            'price' => $breakdown['price'],
        ])->save();
    }

    /**
     * Products in this package that are short of stock for the quantity the package needs.
     * Needs the products relation to be loaded.
     *
     * @return Collection<int, array{product: Product, requested: int, available: int}>
     */
    public function stockShortages(): Collection
    {
        return StockCheck::shortages(
            $this->products->map(fn (Product $product) => ['product' => $product, 'quantity' => $product->pivot->quantity]),
        );
    }

    /**
     * Whether every product in the package is in stock in the quantity it needs.
     */
    public function isAvailable(): bool
    {
        return $this->products->isNotEmpty() && $this->stockShortages()->isEmpty();
    }

    public function discountAmount(): float
    {
        return round(max((float) $this->subtotal - (float) $this->price, 0), 2);
    }

    public function hasDiscount(): bool
    {
        return $this->discountAmount() > 0;
    }

    public function discountPercentage(): int
    {
        return (float) $this->subtotal > 0
            ? (int) round($this->discountAmount() / (float) $this->subtotal * 100)
            : 0;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('quantity')->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
