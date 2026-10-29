<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Product;
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

        $products = Product::query()->active()->orderBy('name');

        return [
            'salesManager' => $user->salesManager,
            'recentOrders' => $user->orders()->latest()->take(5)->get(),
            'activeOrders' => $user->orders()->paid()
                ->whereIn('status', collect(OrderStatus::cases())->filter->isInProgress()->map->value)
                ->latest('paid_at')
                ->get(),
            'paidOrderCount' => $user->orders()->paid()->count(),
            'totalSpent' => (float) $user->orders()->paid()->sum('total_amount'),
            'unpaidOrderCount' => $user->orders()->where('payment_status', '!=', PaymentStatus::Paid)->count(),
            'products' => (clone $products)->take(8)->get(),
            'productCount' => (clone $products)->count(),
        ];
    }
}; ?>

<div class="flex w-full flex-col gap-8">
    @if (auth()->user()->isFranchiseHolder())
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">{{ __('Welcome back, :name', ['name' => str(auth()->user()->name)->before(' ')]) }}</flux:heading>
                <flux:text class="mt-1">{{ auth()->user()->business_name }}</flux:text>
            </div>

            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('member.products')" variant="primary" icon="shopping-bag" wire:navigate>{{ __('Products') }}</flux:button>
                <flux:button :href="route('cart.index')" icon="shopping-cart" wire:navigate>{{ __('Cart') }}</flux:button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <flux:card>
                <div class="flex items-start justify-between gap-4">
                    <flux:heading size="lg">{{ __('My profile') }}</flux:heading>
                    <flux:badge size="sm" color="green">{{ auth()->user()->status->label() }}</flux:badge>
                </div>

                <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Franchise name') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ auth()->user()->business_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Owner') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ auth()->user()->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</dt>
                        <dd class="truncate font-medium text-zinc-900 dark:text-white">{{ auth()->user()->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ auth()->user()->phone }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Delivery address') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">
                            {{ collect([auth()->user()->address, auth()->user()->city, auth()->user()->state, auth()->user()->pincode])->filter()->join(', ') }}
                        </dd>
                    </div>
                </dl>

                <flux:button size="sm" variant="ghost" icon="pencil-square" class="mt-4" :href="route('profile.edit')" wire:navigate>{{ __('Edit profile') }}</flux:button>
            </flux:card>

            <flux:card>
                <flux:heading size="lg">{{ __('My sales manager') }}</flux:heading>

                @if ($salesManager)
                    <div class="mt-4 flex items-start gap-4">
                        <flux:avatar size="lg" :name="$salesManager->name" :initials="$salesManager->initials()" />
                        <dl class="min-w-0 space-y-2 text-sm">
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</dt>
                                <dd class="truncate text-base font-medium text-zinc-900 dark:text-white">{{ $salesManager->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Contact number') }}</dt>
                                <dd class="font-medium text-zinc-900 dark:text-white">
                                    @if ($salesManager->phone)
                                        <a href="tel:{{ $salesManager->phone }}" class="text-brand-600 hover:underline dark:text-brand-300">{{ $salesManager->phone }}</a>
                                    @else
                                        <span class="text-zinc-500">{{ __('Not provided yet') }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Email ID') }}</dt>
                                <dd class="truncate font-medium">
                                    <a href="mailto:{{ $salesManager->email }}" class="text-brand-600 hover:underline dark:text-brand-300">{{ $salesManager->email }}</a>
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <flux:text size="sm" class="mt-4">{{ __('Appointed by the company to help you with products and orders.') }}</flux:text>
                @else
                    <div class="mt-4 flex items-center gap-3 rounded-lg bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <flux:icon name="user" class="size-5 shrink-0" />
                        {{ __('A sales manager has not been assigned to you yet. The company will appoint one shortly.') }}
                    </div>
                @endif
            </flux:card>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:card>
                <flux:text size="sm">{{ __('Orders placed') }}</flux:text>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $paidOrderCount }}</p>
            </flux:card>
            <flux:card>
                <flux:text size="sm">{{ __('Total spent') }}</flux:text>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">₹{{ number_format($totalSpent, 2) }}</p>
            </flux:card>
            <flux:card>
                <flux:text size="sm">{{ __('Awaiting payment') }}</flux:text>
                <p class="mt-1 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $unpaidOrderCount }}</p>
            </flux:card>
        </div>

        @if ($activeOrders->isNotEmpty())
            <div>
                <flux:heading size="lg">{{ __('Orders in progress') }}</flux:heading>

                <div class="mt-4 flex flex-col gap-3">
                    @foreach ($activeOrders as $order)
                        <div wire:key="active-order-{{ $order->id }}" class="flex flex-wrap items-center gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <a href="{{ route('orders.show', $order) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-4">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                    <flux:icon :name="$order->status === \App\Enums\OrderStatus::Shipped ? 'truck' : 'clock'" class="size-5" />
                                </span>
                                <div class="min-w-0">
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Order #:id', ['id' => $order->id]) }} &middot; ₹{{ number_format((float) $order->total_amount, 2) }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __($order->status->customerMessage()) }}</p>
                                    @if ($order->awb_number)
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $order->courier_name ? $order->courier_name.' · ' : '' }}{{ __('AWB') }} {{ $order->awb_number }}
                                        </p>
                                    @endif
                                </div>
                            </a>

                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                                @if ($order->tracking_url)
                                    <flux:button size="sm" icon-trailing="arrow-top-right-on-square" :href="$order->tracking_url" target="_blank" rel="noopener noreferrer">{{ __('Track') }}</flux:button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="lg">{{ __('Available products and offer prices') }}</flux:heading>
                <flux:button size="sm" variant="ghost" icon-trailing="arrow-right" :href="route('member.products')" wire:navigate>
                    {{ __('View all :count products', ['count' => $productCount]) }}
                </flux:button>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-5 md:grid-cols-3 xl:grid-cols-4">
                @forelse ($products as $product)
                    <x-member-product-card :product="$product" wire:key="dash-product-{{ $product->id }}" />
                @empty
                    <flux:text class="col-span-full">{{ __('No products available yet.') }}</flux:text>
                @endforelse
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Order history') }}</flux:heading>
                <flux:button size="sm" variant="ghost" :href="route('orders.index')" wire:navigate>{{ __('View all') }}</flux:button>
            </div>

            <flux:table class="mt-4">
                <flux:table.columns>
                    <flux:table.column>{{ __('Order') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Status') }}</flux:table.column>
                    <flux:table.column>{{ __('Total') }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($recentOrders as $order)
                        <flux:table.row wire:key="dash-order-{{ $order->id }}">
                            <flux:table.cell>#{{ $order->id }}</flux:table.cell>
                            <flux:table.cell>{{ $order->created_at->format('d M Y') }}</flux:table.cell>
                            <flux:table.cell>
                                @if ($order->isPaid())
                                    <flux:badge size="sm" :color="$order->status->color()">{{ $order->status->label() }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="amber">{{ __('Payment pending') }}</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                            <flux:table.cell>
                                <div class="flex items-center justify-end gap-1">
                                    @if ($order->isPaid() && $order->invoice_number)
                                        <flux:button size="sm" variant="ghost" icon="arrow-down-tray" :href="route('orders.invoice', $order)">{{ __('Invoice') }}</flux:button>
                                    @endif
                                    <flux:button size="sm" variant="ghost" :href="route('orders.show', $order)" wire:navigate>
                                        {{ $order->isPaid() ? __('Track') : __('Pay now') }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">{{ __("You haven't placed any orders yet.") }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    @else
        <flux:heading size="lg">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
    @endif
</div>
