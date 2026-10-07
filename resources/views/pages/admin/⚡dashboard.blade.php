<?php

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Admin Dashboard')] class extends Component
{
    public function with(): array
    {
        return [
            'franchiseHolderCount' => User::where('role', UserRole::FranchiseHolder)->count(),
            'pendingApprovalCount' => User::where('role', UserRole::FranchiseHolder)->where('status', UserStatus::Pending)->count(),
            'orderCount' => Order::count(),
            'pendingOrderCount' => Order::where('status', OrderStatus::Pending)->count(),
        ];
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Admin Dashboard') }}</flux:heading>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <flux:subheading>{{ __('Franchise Holders') }}</flux:subheading>
            <flux:heading size="xl">{{ $franchiseHolderCount }}</flux:heading>
        </flux:card>

        <flux:card>
            <flux:subheading>{{ __('Pending Approvals') }}</flux:subheading>
            <flux:heading size="xl">{{ $pendingApprovalCount }}</flux:heading>
            @if ($pendingApprovalCount > 0)
                <flux:link :href="route('admin.franchise-holders.index', ['status' => 'pending'])" wire:navigate>
                    {{ __('Review') }}
                </flux:link>
            @endif
        </flux:card>

        <flux:card>
            <flux:subheading>{{ __('Total Orders') }}</flux:subheading>
            <flux:heading size="xl">{{ $orderCount }}</flux:heading>
        </flux:card>

        <flux:card>
            <flux:subheading>{{ __('Pending Orders') }}</flux:subheading>
            <flux:heading size="xl">{{ $pendingOrderCount }}</flux:heading>
        </flux:card>
    </div>

    <div class="mt-8 flex flex-wrap gap-4">
        <flux:button :href="route('admin.franchise-holders.index')" variant="primary" wire:navigate>{{ __('Manage Franchise Holders') }}</flux:button>
        <flux:button :href="route('admin.sales-managers.index')" variant="filled" wire:navigate>{{ __('Manage Sales Managers') }}</flux:button>
        <flux:button :href="route('admin.orders.index')" variant="filled" wire:navigate>{{ __('View Orders') }}</flux:button>
    </div>
</section>
