<?php

namespace App\Concerns;

use App\Models\User;
use App\Support\FranchiseOrderHistory;
use Livewire\Attributes\Url;

/**
 * Lets a Livewire page show the purchase history of one franchise with a month filter.
 * The page also needs Livewire's WithPagination trait and renders <x-franchise-order-history>.
 */
trait ShowsFranchiseOrderHistory
{
    /**
     * The month to show as "YYYY-MM", or empty for every month.
     */
    #[Url]
    public string $month = '';

    public function updatedMonth(): void
    {
        $this->resetPage();
    }

    /**
     * Everything <x-franchise-order-history> needs for the franchise.
     *
     * @return array{summary: array<string, mixed>, months: mixed, monthOptions: mixed, products: mixed, orders: mixed, selectedMonth: string}
     */
    protected function franchiseHistory(User $holder): array
    {
        $history = new FranchiseOrderHistory($holder);
        $selectedMonth = FranchiseOrderHistory::monthRange($this->month) ? $this->month : '';

        return [
            'summary' => $history->summary($selectedMonth),
            'months' => $history->months(),
            'monthOptions' => $history->monthOptions(),
            'products' => $history->products($selectedMonth),
            'orders' => $history->orders($selectedMonth)->paginate(10),
            'selectedMonth' => $selectedMonth,
        ];
    }
}
