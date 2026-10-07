<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Franchise Holder')] class extends Component
{
    public User $holder;

    public ?int $salesManagerId = null;

    public Collection $salesManagers;

    public function mount(User $user): void
    {
        abort_unless($user->role === UserRole::FranchiseHolder, 404);

        $this->holder = $user;
        $this->salesManagerId = $user->sales_manager_id;
        $this->salesManagers = User::where('role', UserRole::SalesManager)->orderBy('name')->get();
    }

    public function updateStatus(string $status): void
    {
        $this->holder->update(['status' => UserStatus::from($status)]);

        Flux::toast(variant: 'success', text: __(':name is now :status.', ['name' => $this->holder->name, 'status' => $this->holder->status->label()]));
    }

    public function assignSalesManager(): void
    {
        $this->holder->update(['sales_manager_id' => $this->salesManagerId]);

        Flux::toast(variant: 'success', text: __('Sales manager updated.'));
    }

    public function with(): array
    {
        return [
            'orders' => $this->holder->orders()->latest()->paginate(10),
        ];
    }
}; ?>

<section class="w-full max-w-3xl">
    <flux:button variant="ghost" :href="route('admin.franchise-holders.index')" wire:navigate icon="arrow-left">
        {{ __('Back') }}
    </flux:button>

    <div class="mt-4 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ $holder->name }}</flux:heading>
            <flux:text class="text-zinc-500">{{ $holder->email }}</flux:text>
        </div>
        <flux:badge>{{ $holder->status->label() }}</flux:badge>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <flux:card>
            <flux:subheading>{{ __('Business') }}</flux:subheading>
            <flux:text>{{ $holder->business_name }}</flux:text>
            <flux:text>{{ $holder->phone }}</flux:text>
            <flux:text>{{ $holder->address }}, {{ $holder->city }}, {{ $holder->state }} {{ $holder->pincode }}</flux:text>
        </flux:card>

        <flux:card>
            <flux:subheading>{{ __('Sales Manager') }}</flux:subheading>
            <flux:select wire:model="salesManagerId" wire:change="assignSalesManager">
                <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                @foreach ($salesManagers as $manager)
                    <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:card>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        @if ($holder->status !== UserStatus::Approved)
            <flux:button variant="primary" wire:click="updateStatus('approved')">{{ __('Approve') }}</flux:button>
        @endif
        @if ($holder->status !== UserStatus::Suspended)
            <flux:button variant="danger" wire:click="updateStatus('suspended')">{{ __('Suspend') }}</flux:button>
        @endif
    </div>

    <flux:heading size="lg" class="mt-8">{{ __('Order History') }}</flux:heading>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Order') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Total') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($orders as $order)
                <flux:table.row wire:key="holder-order-{{ $order->id }}">
                    <flux:table.cell>
                        <flux:link :href="route('admin.orders.show', $order)" wire:navigate>#{{ $order->id }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $order->created_at->format('d M Y') }}</flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $order->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>₹{{ number_format((float) $order->total_amount, 2) }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __('No orders yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-4">
        {{ $orders->links() }}
    </div>
</section>
