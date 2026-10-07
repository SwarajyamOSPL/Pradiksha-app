<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Dashboard')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $holders = User::where('role', UserRole::FranchiseHolder);
        $orderCountsByStatus = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'orderValue' => (float) Order::where('status', '!=', OrderStatus::Cancelled)->sum('total_amount'),
            'orderCount' => $orderCountsByStatus->sum(),
            'orderCountsByStatus' => $orderCountsByStatus,
            'pendingOrderCount' => (int) ($orderCountsByStatus[OrderStatus::Pending->value] ?? 0),
            'franchiseHolderCount' => (clone $holders)->count(),
            'approvedHolderCount' => (clone $holders)->where('status', UserStatus::Approved)->count(),
            'pendingApprovalCount' => (clone $holders)->where('status', UserStatus::Pending)->count(),
            'awaitingApproval' => (clone $holders)->where('status', UserStatus::Pending)->latest()->limit(5)->get(),
            'activeProductCount' => Product::where('is_active', true)->count(),
            'packageCount' => Package::count(),
            'recentOrders' => Order::with('user')->latest()->limit(6)->get(),
        ];
    }
}; ?>

<section class="w-full space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Welcome back, :name', ['name' => str(auth()->user()->name)->before(' ')]) }}</flux:heading>
            <flux:text class="mt-1">{{ now()->format('l, d F Y') }} &middot; {{ __('Here is what is happening across your franchise network.') }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button icon="plus" variant="primary" :href="route('admin.products.create')" wire:navigate>{{ __('Add product') }}</flux:button>
            <flux:button icon="plus" :href="route('admin.packages.create')" wire:navigate>{{ __('Add package') }}</flux:button>
            <flux:button icon="plus" :href="route('admin.sales-managers.create')" wire:navigate>{{ __('Add sales manager') }}</flux:button>
        </div>
    </div>

    @if ($pendingApprovalCount > 0)
        <flux:callout icon="clock" color="amber" inline>
            <flux:callout.heading>
                {{ trans_choice(':count franchise holder is waiting for approval|:count franchise holders are waiting for approval', $pendingApprovalCount) }}
            </flux:callout.heading>
            <flux:callout.text>{{ __('They cannot see pricing or place orders until you approve them.') }}</flux:callout.text>

            <x-slot name="actions">
                <flux:button :href="route('admin.franchise-holders.index', ['status' => 'pending'])" wire:navigate>{{ __('Review now') }}</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:text size="sm">{{ __('Order value') }}</flux:text>
                    <p class="mt-2 truncate text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">₹{{ number_format($orderValue, 2) }}</p>
                    <flux:text size="sm" class="mt-1">{{ __('Excludes cancelled orders') }}</flux:text>
                </div>
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                    <flux:icon name="banknotes" class="size-6" />
                </span>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:text size="sm">{{ __('Total orders') }}</flux:text>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ number_format($orderCount) }}</p>
                    <flux:text size="sm" class="mt-1">
                        @if ($pendingOrderCount > 0)
                            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" wire:navigate class="font-medium text-amber-600 hover:underline dark:text-amber-400">
                                {{ __(':count awaiting action', ['count' => $pendingOrderCount]) }}
                            </a>
                        @else
                            {{ __('Nothing waiting on you') }}
                        @endif
                    </flux:text>
                </div>
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                    <flux:icon name="receipt-percent" class="size-6" />
                </span>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:text size="sm">{{ __('Franchise holders') }}</flux:text>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ number_format($franchiseHolderCount) }}</p>
                    <flux:text size="sm" class="mt-1">{{ __(':approved approved · :pending pending', ['approved' => $approvedHolderCount, 'pending' => $pendingApprovalCount]) }}</flux:text>
                </div>
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                    <flux:icon name="users" class="size-6" />
                </span>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <flux:text size="sm">{{ __('Active products') }}</flux:text>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ number_format($activeProductCount) }}</p>
                    <flux:text size="sm" class="mt-1">{{ trans_choice(':count package|:count packages', $packageCount) }}</flux:text>
                </div>
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                    <flux:icon name="cube" class="size-6" />
                </span>
            </div>
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <flux:card class="lg:col-span-2">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ __('Recent orders') }}</flux:heading>
                    <flux:text size="sm" class="mt-1">{{ __('The latest orders placed by franchise holders.') }}</flux:text>
                </div>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('admin.orders.index')" wire:navigate>{{ __('View all') }}</flux:button>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="mt-6 flex flex-col items-center gap-2 rounded-lg border border-dashed border-zinc-300 px-6 py-10 text-center dark:border-zinc-700">
                    <flux:icon name="receipt-percent" class="size-8 text-zinc-400" />
                    <flux:text>{{ __('No orders yet. They will show up here as franchise holders start ordering.') }}</flux:text>
                </div>
            @else
                <flux:table bleed container:class="mt-4">
                    <flux:table.columns>
                        <flux:table.column>{{ __('Order') }}</flux:table.column>
                        <flux:table.column>{{ __('Franchise holder') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Total') }}</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($recentOrders as $order)
                            <flux:table.row wire:key="recent-order-{{ $order->id }}">
                                <flux:table.cell variant="strong">
                                    #{{ $order->id }}
                                    <span class="block text-xs font-normal text-zinc-500 dark:text-zinc-400">{{ $order->created_at->format('d M, h:i A') }}</span>
                                </flux:table.cell>
                                <flux:table.cell>{{ $order->user->business_name ?: $order->user->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm" :color="$order->status->color()" inset="top bottom">{{ $order->status->label() }}</flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end" variant="strong">₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:button size="sm" variant="ghost" icon="eye" :href="route('admin.orders.show', $order)" wire:navigate inset="top bottom" aria-label="{{ __('View order') }}" />
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @endif
        </flux:card>

        <div class="space-y-6">
            <flux:card>
                <flux:heading size="lg">{{ __('Orders by status') }}</flux:heading>

                <div class="mt-5 space-y-4">
                    @foreach (OrderStatus::cases() as $status)
                        @php
                            $count = (int) ($orderCountsByStatus[$status->value] ?? 0);
                            $share = $orderCount > 0 ? round($count / $orderCount * 100) : 0;
                        @endphp
                        <a href="{{ route('admin.orders.index', ['status' => $status->value]) }}" wire:navigate wire:key="status-{{ $status->value }}" class="group block">
                            <div class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2 text-zinc-700 group-hover:text-zinc-900 dark:text-zinc-300 dark:group-hover:text-white">
                                    <flux:badge size="sm" :color="$status->color()" inset="top bottom">{{ $status->label() }}</flux:badge>
                                </span>
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $count }}</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $share }}%"></div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </flux:card>

            <flux:card>
                <div class="flex items-center justify-between gap-4">
                    <flux:heading size="lg">{{ __('Awaiting approval') }}</flux:heading>
                    @if ($pendingApprovalCount > 5)
                        <flux:button size="sm" variant="ghost" :href="route('admin.franchise-holders.index', ['status' => 'pending'])" wire:navigate>{{ __('View all') }}</flux:button>
                    @endif
                </div>

                @forelse ($awaitingApproval as $holder)
                    <a href="{{ route('admin.franchise-holders.show', $holder) }}" wire:navigate wire:key="holder-{{ $holder->id }}" class="-mx-2 mt-3 flex items-center gap-3 rounded-lg px-2 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700/50">
                        <flux:avatar size="sm" :name="$holder->name" :initials="$holder->initials()" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $holder->business_name ?: $holder->name }}</p>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ collect([$holder->city, $holder->state])->filter()->join(', ') ?: $holder->email }}</p>
                        </div>
                        <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ $holder->created_at->diffForHumans(short: true) }}</span>
                    </a>
                @empty
                    <div class="mt-4 flex items-center gap-3 rounded-lg bg-brand-50 px-4 py-3 text-sm text-brand-800 dark:bg-brand-500/10 dark:text-brand-200">
                        <flux:icon name="check-circle" class="size-5 shrink-0" />
                        {{ __('All caught up. No one is waiting for approval.') }}
                    </div>
                @endforelse
            </flux:card>
        </div>
    </div>
</section>
