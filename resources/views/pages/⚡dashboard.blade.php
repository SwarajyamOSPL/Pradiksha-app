<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component
{
    public function mount(): void
    {
        if (Auth::user()->isAdmin()) {
            $this->redirectRoute('admin.dashboard', navigate: true);
        }

        if (Auth::user()->isSalesManager()) {
            $this->redirectRoute('sales.dashboard', navigate: true);
        }
    }

    public function with(): array
    {
        $user = Auth::user();

        if (! $user->isFranchiseHolder()) {
            return [];
        }

        return [
            'recentOrders' => $user->orders()->latest()->take(5)->get(),
            'salesManager' => $user->salesManager,
        ];
    }
}; ?>

<div class="flex w-full flex-col gap-6">
        @if (auth()->user()->isFranchiseHolder())
            @if ($salesManager)
                <flux:callout icon="user">
                    <flux:callout.heading>{{ __('Your sales manager') }}</flux:callout.heading>
                    <flux:callout.text>{{ $salesManager->name }} &middot; {{ $salesManager->email }}</flux:callout.text>
                </flux:callout>
            @endif

            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Recent Orders') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('orders.index')" wire:navigate>{{ __('View all') }}</flux:button>
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Order') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Total') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($recentOrders as $order)
                        <flux:table.row wire:key="dash-order-{{ $order->id }}" class="cursor-pointer" onclick="window.location='{{ route('orders.show', $order) }}'">
                            <flux:table.cell>#{{ $order->id }}</flux:table.cell>
                            <flux:table.cell>{{ $order->created_at->format('d M Y') }}</flux:table.cell>
                            <flux:table.cell><flux:badge>{{ $order->status->label() }}</flux:badge></flux:table.cell>
                            <flux:table.cell>₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">{{ __("You haven't placed any orders yet.") }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="flex gap-3">
                <flux:button :href="route('products.index')" variant="primary" wire:navigate>{{ __('Browse Products') }}</flux:button>
                <flux:button :href="route('packages.index')" variant="filled" wire:navigate>{{ __('Browse Packages') }}</flux:button>
            </div>
        @else
            <flux:heading size="lg">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        @endif
    </div>
