<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sales Managers')] class extends Component
{
    use WithPagination;

    public function toggleStatus(int $userId): void
    {
        $manager = User::query()->where('role', UserRole::SalesManager)->findOrFail($userId);
        $manager->update(['status' => $manager->status === UserStatus::Suspended ? UserStatus::Approved : UserStatus::Suspended]);

        Flux::toast(variant: 'success', text: __(':name is now :status.', ['name' => $manager->name, 'status' => $manager->status->label()]));
    }

    public function with(): array
    {
        return [
            'salesManagers' => User::query()
                ->where('role', UserRole::SalesManager)
                ->withCount('franchiseHolders')
                ->orderBy('name')
                ->paginate(15),
        ];
    }
}; ?>

<section class="w-full">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Sales Managers') }}</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.sales-managers.create')" wire:navigate>
            {{ __('Add Sales Manager') }}
        </flux:button>
    </div>

    <flux:table class="mt-6">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Email') }}</flux:table.column>
            <flux:table.column>{{ __('Franchise Holders') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($salesManagers as $manager)
                <flux:table.row wire:key="manager-{{ $manager->id }}">
                    <flux:table.cell>{{ $manager->name }}</flux:table.cell>
                    <flux:table.cell>{{ $manager->email }}</flux:table.cell>
                    <flux:table.cell>{{ $manager->franchise_holders_count }}</flux:table.cell>
                    <flux:table.cell><flux:badge>{{ $manager->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-2">
                            <flux:button size="sm" variant="ghost" :href="route('admin.sales-managers.edit', $manager)" wire:navigate>
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button size="sm" variant="danger" wire:click="toggleStatus({{ $manager->id }})">
                                {{ $manager->status->value === 'suspended' ? __('Reactivate') : __('Suspend') }}
                            </flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No sales managers yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <div class="mt-6">
        {{ $salesManagers->links() }}
    </div>
</section>
