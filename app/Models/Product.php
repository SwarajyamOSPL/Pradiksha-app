<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $category
 * @property string|null $description
 * @property string|null $image_path
 * @property float|null $price
 * @property float|null $mrp
 * @property int $stock
 * @property bool $is_active
 * @property string|null $source_url
 */
#[Fillable(['name', 'slug', 'category', 'description', 'image_path', 'price', 'mrp', 'stock', 'is_active', 'source_url'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * At or below this many units a product is shown as "low stock".
     */
    public const LOW_STOCK_THRESHOLD = 10;

    protected $attributes = [
        'is_active' => true,
        'stock' => 0,
    ];

    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            if ($product->wasChanged('price')) {
                $product->packages()->get()->each->refreshPrice();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'mrp' => 'decimal:2',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Whether the offer price is genuinely below the printed MRP, so the saving is worth showing.
     */
    public function hasOffer(): bool
    {
        return $this->mrp !== null && $this->price !== null && (float) $this->price > 0 && (float) $this->mrp > (float) $this->price;
    }

    public function savingsPercentage(): int
    {
        return $this->hasOffer() ? (int) round(((float) $this->mrp - (float) $this->price) / (float) $this->mrp * 100) : 0;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= self::LOW_STOCK_THRESHOLD;
    }

    public function hasStockFor(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * A short line for customers: how many are available, or that the product is out of stock.
     */
    public function stockLabel(): string
    {
        return match (true) {
            ! $this->isInStock() => 'Out of stock',
            $this->isLowStock() => 'Only '.$this->stock.' left',
            default => 'In stock: '.$this->stock,
        };
    }

    /**
     * Text colour classes matching the stock level: red when out, amber when low, green otherwise.
     */
    public function stockTextClasses(): string
    {
        return match (true) {
            ! $this->isInStock() => 'text-red-600 dark:text-red-400',
            $this->isLowStock() => 'text-amber-600 dark:text-amber-400',
            default => 'text-green-700 dark:text-green-400',
        };
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stock', '<=', 0);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0)->where('stock', '<=', self::LOW_STOCK_THRESHOLD);
    }

    /**
     * @return BelongsToMany<Package, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(Package::class)->withPivot('quantity')->withTimestamps();
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
