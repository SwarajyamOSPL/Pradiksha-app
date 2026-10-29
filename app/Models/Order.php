<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $package_id
 * @property OrderType $type
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property string|null $payment_method
 * @property string|null $razorpay_order_id
 * @property string|null $razorpay_payment_id
 * @property float $subtotal
 * @property float $discount_amount
 * @property float $tax_rate
 * @property bool $tax_inclusive
 * @property float $tax_amount
 * @property float $total_amount
 * @property string|null $invoice_number
 * @property Carbon|null $paid_at
 * @property string|null $notes
 */
#[Fillable([
    'user_id', 'package_id', 'type', 'status', 'payment_status', 'payment_method', 'razorpay_order_id', 'razorpay_payment_id',
    'subtotal', 'discount_amount', 'tax_rate', 'tax_inclusive', 'tax_amount', 'total_amount', 'invoice_number', 'paid_at', 'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
        'payment_status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_inclusive' => 'boolean',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Orders whose payment has been verified, i.e. orders that are actually placed.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Paid);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Package, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
