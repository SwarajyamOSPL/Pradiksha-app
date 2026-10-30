<?php

use App\Concerns\ShowsFranchiseOrderHistory;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sales Dashboard')] class extends Component
{
    use ShowsFranchiseOrderHistory, WithPagination;

    #[Url]
    public string $franchise = '';

    #[Url]
    public string $status = '';

    public function updatedFranchise(): void
    {
        $this->month = '';
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $manager = Auth::user();
        $franchises = $manager->franchiseHolders()->orderBy('business_name')->orderBy('name')->get();

        $allOrders = fn () => Order::query()->paid()->whereIn('user_id', $franchises->pluck('id'));

        // Only a franchise assigned to this manager can be opened.
        $selectedFranchise = $this->franchise !== '' ? $franchises->firstWhere('id', (int) $this->franchise) : null;

        return [
            'franchises' => $franchises,
            'selectedFranchise' => $selectedFranchise,
            'history' => $selectedFranchise ? $this->franchiseHistory($selectedFranchise) : null,
            'orderCount' => $allOrders()->count(),
            'orderValue' => (float) $allOrders()->where('status', '!=', OrderStatus::Cancelled)->sum('total_amount'),
            'monthOrderCount' => $allOrders()->where('paid_at', '>=', now()->startOfMonth())->count(),
            'orders' => $selectedFranchise ? null : $allOrders()
                ->with('user')
                ->withCount('items')
                ->when($this->franchise, fn ($query) => $query->where('user_id', $this->franchise))
                ->when($this->status, fn ($query) => $query->where('status', $this->status))
                ->latest('paid_at')
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Welcome back, :name', ['name' => str(auth()->user()->name)->before(' ')]) }}</flux:heading>
            <flux:text class="mt-1">{{ __('Orders placed by the franchises assigned to you.') }}</flux:text>
        </div>

        <flux:button icon="users" :href="route('sales.franchise-holders.index')" wire:navigate>{{ __('My franchises') }}</flux:button>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:card>
            <flux:text size="sm">{{ __('Assigned franchises') }}</flux:text>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $franchises->count() }}</p>
        </flux:card>
        <flux:card>
            <flux:text size="sm">{{ __('Total orders') }}</flux:text>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ number_format($orderCount) }}</p>
        </flux:card>
        <flux:card>
            <flux:text size="sm">{{ __('Orders this month') }}</flux:text>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ number_format($monthOrderCount) }}</p>
        </flux:card>
        <flux:card>
            <flux:text size="sm">{{ __('Order value') }}</flux:text>
            <p class="mt-2 truncate text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">₹{{ number_format($orderValue, 2) }}</p>
            <flux:text size="sm" class="mt-1">{{ __('Excludes cancelled orders') }}</flux:text>
        </flux:card>
    </div>

    <div>
        <flux:heading size="lg">{{ __('Order history') }}</flux:heading>

        <div class="mt-4 flex flex-wrap gap-4">
            <flux:select wire:model.live="franchise" class="max-w-xs">
                <flux:select.option value="">{{ __('All franchises') }}</flux:select.option>
                @foreach ($franchises as $holder)
                    <flux:select.option value="{{ $holder->id }}">{{ $holder->business_name ?: $holder->name }}{{ $holder->city ? ' ('.$holder->city.')' : '' }}</flux:select.option>
                @endforeach
            </flux:select>

            @unless ($selectedFranchise)
                <flux:select wire:model.live="status" class="max-w-xs">
                    <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                    @foreach (OrderStatus::cases() as $case)
                        <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endunless
        </div>

        @if ($selectedFranchise)
            <div class="mt-6 flex flex-wrap items-start justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700" data-selected-franchise>
                <div>
                    <flux:heading>{{ $selectedFranchise->business_name ?: $selectedFranchise->name }}</flux:heading>
                    <flux:text size="sm" class="mt-1">
                        {{ $selectedFranchise->name }}
                        @if ($selectedFranchise->phone) &middot; {{ $selectedFranchise->phone }} @endif
                        @if ($selectedFranchise->city) &middot; {{ $selectedFranchise->city }} @endif
                    </flux:text>
                </div>
                <flux:button size="sm" :href="route('sales.franchise-holders.show', $selectedFranchise)" wire:navigate>{{ __('Open franchise') }}</flux:button>
            </div>

            <x-franchise-order-history :history="$history" order-route="sales.orders.show" class="mt-6" />
        @else
            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Order') }}</flux:table.column>
                    <flux:table.column>{{ __('Franchise') }}</flux:table.column>
                    <flux:table.column>{{ __('Items') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($orders as $order)
                        <flux:table.row wire:key="sales-order-{{ $order->id }}">
                            <flux:table.cell variant="strong">
                                #{{ $order->id }}
                                <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ ($order->paid_at ?? $order->created_at)->format('d M Y, h:i A') }}</span>
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $order->user->business_name ?: $order->user->name }}
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $order->user->city }}</span>
                            </flux:table.cell>
                            <flux:table.cell>{{ $order->items_count }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge size="sm" :color="$order->status->color()" inset="top bottom">{{ $order->status->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell align="end" variant="strong">₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button size="sm" variant="ghost" icon="eye" :href="route('sales.orders.show', $order)" wire:navigate inset="top bottom" aria-label="{{ __('View order') }}" />
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">
                                {{ $franchises->isEmpty() ? __("You don't have any franchises assigned yet.") : __('No orders found.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</section>
