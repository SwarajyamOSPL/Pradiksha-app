<?php

use App\Actions\ApproveFranchiseHolder;
use App\Concerns\ShowsFranchiseOrderHistory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Franchise Holder')] class extends Component
{
    use ShowsFranchiseOrderHistory, WithPagination;

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
        $newStatus = UserStatus::from($status);

        if ($newStatus === UserStatus::Approved) {
            app(ApproveFranchiseHolder::class)($this->holder);
        } else {
            $this->holder->update(['status' => $newStatus]);
        }

        Flux::toast(variant: 'success', text: __(':name is now :status.', ['name' => $this->holder->name, 'status' => $this->holder->status->label()]));
    }

    public function assignSalesManager(): void
    {
        $this->holder->update(['sales_manager_id' => $this->salesManagerId]);

        Flux::toast(variant: 'success', text: __('Sales manager updated.'));
    }

    public function with(): array
    {
        return ['history' => $this->franchiseHistory($this->holder)];
    }
}; ?>

<section class="w-full max-w-5xl">
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

    <x-franchise-order-history :history="$history" order-route="admin.orders.show" class="mt-8" />
</section>
