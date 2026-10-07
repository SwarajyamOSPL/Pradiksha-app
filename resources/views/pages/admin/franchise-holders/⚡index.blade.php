<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Franchise Holders')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    /** @var array<int, int|null> */
    public array $assignments = [];

    public function updateStatus(int $userId, string $status): void
    {
        $user = User::query()->where('role', UserRole::FranchiseHolder)->findOrFail($userId);
        $user->update(['status' => UserStatus::from($status)]);

        Flux::toast(variant: 'success', text: __(':name is now :status.', ['name' => $user->name, 'status' => $user->status->label()]));
    }

    public function assignSalesManager(int $userId): void
    {
        $user = User::query()->where('role', UserRole::FranchiseHolder)->findOrFail($userId);
        $user->update(['sales_manager_id' => $this->assignments[$userId] ?: null]);

        Flux::toast(variant: 'success', text: __('Sales manager updated for :name.', ['name' => $user->name]));
    }

    public function with(): array
    {
        $franchiseHolders = User::query()
            ->where('role', UserRole::FranchiseHolder)
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('business_name', 'like', "%{$this->search}%")))
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->paginate(15);

        foreach ($franchiseHolders as $holder) {
            $this->assignments[$holder->id] ??= $holder->sales_manager_id;
        }

        return [
            'franchiseHolders' => $franchiseHolders,
            'salesManagers' => User::where('role', UserRole::SalesManager)->orderBy('name')->get(),
        ];
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl">{{ __('Franchise Holders') }}</flux:heading>

    <div class="mt-6 flex flex-wrap gap-4">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search name, email, business...')" icon="magnifying-glass" class="max-w-sm" />

        <flux:select wire:model.live="status" class="max-w-xs">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            <flux:select.option value="pending">{{ __('Pending') }}</flux:select.option>
            <flux:select.option value="approved">{{ __('Approved') }}</flux:select.option>
            <flux:select.option value="suspended">{{ __('Suspended') }}</flux:select.option>
        </flux:select>
    </div>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Business') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Sales Manager') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($franchiseHolders as $holder)
                <flux:table.row wire:key="holder-{{ $holder->id }}">
                    <flux:table.cell>
                        <flux:link :href="route('admin.franchise-holders.show', $holder)" wire:navigate>{{ $holder->name }}</flux:link>
                        <flux:text class="text-zinc-500">{{ $holder->email }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $holder->business_name }}</flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $holder->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        <flux:select wire:model="assignments.{{ $holder->id }}" wire:change="assignSalesManager({{ $holder->id }})" size="sm">
                            <flux:select.option value="">{{ __('Unassigned') }}</flux:select.option>
                            @foreach ($salesManagers as $manager)
                                <flux:select.option value="{{ $manager->id }}">{{ $manager->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-2">
                            @if ($holder->status !== UserStatus::Approved)
                                <flux:button size="sm" variant="primary" wire:click="updateStatus({{ $holder->id }}, 'approved')">
                                    {{ __('Approve') }}
                                </flux:button>
                            @endif
                            @if ($holder->status !== UserStatus::Suspended)
                                <flux:button size="sm" variant="danger" wire:click="updateStatus({{ $holder->id }}, 'suspended')">
                                    {{ __('Suspend') }}
                                </flux:button>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No franchise holders found.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $franchiseHolders->links() }}
    </div>
</section>
