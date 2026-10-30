@props(['history', 'orderRoute'])

{{-- Purchase history of one franchise (from ShowsFranchiseOrderHistory), inside a Livewire page that has a $month property. --}}
@php
    ['summary' => $summary, 'months' => $months, 'monthOptions' => $monthOptions, 'products' => $products, 'orders' => $orders, 'selectedMonth' => $month] = $history;
@endphp

<div {{ $attributes->class(['space-y-6']) }} data-franchise-history>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Purchase history') }}</flux:heading>
            <flux:text size="sm" class="mt-1">
                {{ $month && isset($monthOptions[$month]) ? __('Showing :month.', ['month' => $monthOptions[$month]]) : __('Showing all months.') }}
                {{ __('Cancelled orders are not counted.') }}
            </flux:text>
        </div>

        <flux:select wire:model.live="month" class="max-w-48" :aria-label="__('Month')">
            <flux:select.option value="">{{ __('All months') }}</flux:select.option>
            @foreach ($monthOptions as $key => $label)
                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:text size="sm">{{ __('Orders') }}</flux:text>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white" data-history-orders>{{ number_format($summary['orders']) }}</p>
        </flux:card>
        <flux:card>
            <flux:text size="sm">{{ __('Amount ordered') }}</flux:text>
            <p class="mt-2 truncate text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white" data-history-amount>₹{{ number_format($summary['amount'], 2) }}</p>
        </flux:card>
        <flux:card>
            <flux:text size="sm">{{ __('Units ordered') }}</flux:text>
            <p class="mt-2 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white" data-history-units>{{ number_format($summary['units']) }}</p>
            @if ($summary['lastOrderAt'])
                <flux:text size="sm" class="mt-1">{{ __('Last order :date', ['date' => $summary['lastOrderAt']->format('d M Y')]) }}</flux:text>
            @endif
        </flux:card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <flux:card>
            <flux:heading>{{ __('Month wise') }}</flux:heading>

            <flux:table bleed container:class="mt-3" data-history-months>
                <flux:table.columns>
                    <flux:table.column>{{ __('Month') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Orders') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Units') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('Amount') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($months as $row)
                        <flux:table.row wire:key="history-month-{{ $row['key'] }}" class="{{ $month === $row['key'] ? 'bg-brand-50 dark:bg-brand-500/10' : '' }}">
                            <flux:table.cell variant="strong">
                                <button type="button" wire:click="$set('month', '{{ $month === $row['key'] ? '' : $row['key'] }}')" class="cursor-pointer text-left hover:underline">{{ $row['label'] }}</button>
                            </flux:table.cell>
                            <flux:table.cell align="end">{{ $row['orders'] }}</flux:table.cell>
                            <flux:table.cell align="end">{{ $row['units'] }}</flux:table.cell>
                            <flux:table.cell align="end" variant="strong">₹{{ number_format($row['amount'], 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">{{ __('No orders yet.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </flux:card>

        <flux:card>
            <flux:heading>{{ __('Products ordered') }}</flux:heading>

            <div class="mt-3 divide-y divide-zinc-200 dark:divide-zinc-700" data-history-products>
                @forelse ($products as $product)
                    <div class="flex items-center justify-between gap-4 py-2 text-sm" wire:key="history-product-{{ $loop->index }}">
                        <span class="min-w-0 truncate text-zinc-800 dark:text-zinc-200">{{ $product->name }}</span>
                        <span class="shrink-0 font-medium text-zinc-900 dark:text-white">&times;{{ number_format((int) $product->units) }}</span>
                    </div>
                @empty
                    <flux:text class="py-2">{{ __('No products ordered in this period.') }}</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>

    <div>
        <flux:heading>{{ __('Orders, date wise') }}</flux:heading>

        <div class="mt-3 space-y-3" data-history-orders-list>
            @forelse ($orders as $order)
                <flux:card wire:key="history-order-{{ $order->id }}" class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <flux:link :href="route($orderRoute, $order)" wire:navigate class="font-semibold">{{ __('Order #:id', ['id' => $order->id]) }}</flux:link>
                            <span class="ml-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $order->paid_at->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                            <span class="font-semibold text-zinc-900 dark:text-white">₹{{ number_format((float) $order->total_amount, 2) }}</span>
                        </div>
                    </div>

                    <ul class="grid grid-cols-1 gap-x-6 gap-y-1 text-sm text-zinc-700 sm:grid-cols-2 dark:text-zinc-300">
                        @foreach ($order->items as $item)
                            <li class="flex justify-between gap-3" wire:key="history-order-{{ $order->id }}-item-{{ $item->id }}">
                                <span class="min-w-0 truncate">{{ $item->product_name }}</span>
                                <span class="shrink-0 text-zinc-500 dark:text-zinc-400">&times;{{ $item->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                </flux:card>
            @empty
                <flux:text>{{ __('No orders found.') }}</flux:text>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    </div>
</div>
