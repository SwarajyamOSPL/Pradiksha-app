<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The purchase history of one franchise: placed (paid) orders with the products in them, summed up
 * month by month. Used by the sales manager and admin screens so both see the same numbers.
 * Cancelled orders are listed but never counted towards amounts or units.
 */
class FranchiseOrderHistory
{
    public function __construct(private readonly User $holder) {}

    /**
     * Placed orders of the franchise, optionally limited to one month ("YYYY-MM").
     *
     * @return Builder<Order>
     */
    private function placedOrders(?string $month = null): Builder
    {
        $range = self::monthRange($month);

        return Order::query()
            ->paid()
            ->where('user_id', $this->holder->id)
            ->when($range, fn (Builder $query) => $query->whereBetween('paid_at', $range));
    }

    /**
     * Start and end of a "YYYY-MM" month, or null when no valid month was given.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function monthRange(?string $month): ?array
    {
        if (! $month || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return null;
        }

        $start = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    /**
     * Totals over the chosen month, or over every month when none is chosen.
     *
     * @return array{orders: int, amount: float, units: int, lastOrderAt: Carbon|null}
     */
    public function summary(?string $month = null): array
    {
        $counted = $this->placedOrders($month)->where('status', '!=', OrderStatus::Cancelled);

        $lastOrderAt = $this->placedOrders()->max('paid_at');

        return [
            'orders' => (clone $counted)->count(),
            'amount' => (float) (clone $counted)->sum('total_amount'),
            'units' => (int) OrderItem::query()->whereIn('order_id', (clone $counted)->select('id'))->sum('quantity'),
            'lastOrderAt' => $lastOrderAt ? Carbon::parse($lastOrderAt) : null,
        ];
    }

    /**
     * One row per month that has orders, newest first.
     *
     * @return Collection<int, array{key: string, label: string, orders: int, units: int, amount: float}>
     */
    public function months(): Collection
    {
        return $this->placedOrders()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->withSum('items as units', 'quantity')
            ->get(['id', 'paid_at', 'total_amount'])
            ->groupBy(fn (Order $order) => $order->paid_at->format('Y-m'))
            ->map(fn (Collection $orders, string $key) => [
                'key' => $key,
                'label' => Carbon::createFromFormat('Y-m-d', $key.'-01')->format('F Y'),
                'orders' => $orders->count(),
                'units' => (int) $orders->sum('units'),
                'amount' => round((float) $orders->sum('total_amount'), 2),
            ])
            ->sortKeysDesc()
            ->values();
    }

    /**
     * Months that have any placed order, cancelled ones included, newest first, for the month filter.
     *
     * @return Collection<string, string>
     */
    public function monthOptions(): Collection
    {
        return $this->placedOrders()
            ->get(['paid_at'])
            ->map(fn (Order $order) => $order->paid_at->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (string $key) => [$key => Carbon::createFromFormat('Y-m-d', $key.'-01')->format('F Y')]);
    }

    /**
     * The orders to list, newest first, with their products.
     *
     * @return Builder<Order>
     */
    public function orders(?string $month = null): Builder
    {
        return $this->placedOrders($month)->with('items')->latest('paid_at');
    }

    /**
     * How many units of each product the franchise ordered in the chosen month (or overall), most first.
     *
     * @return Collection<int, object{name: string, units: int}>
     */
    public function products(?string $month = null): Collection
    {
        $counted = $this->placedOrders($month)->where('status', '!=', OrderStatus::Cancelled)->select('id');

        return OrderItem::query()
            ->whereIn('order_id', $counted)
            ->selectRaw('product_name as name, sum(quantity) as units')
            ->groupBy('product_name')
            ->orderByDesc('units')
            ->orderBy('product_name')
            ->get();
    }
}
