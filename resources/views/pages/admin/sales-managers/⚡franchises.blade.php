<?php

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Assign Franchises')] class extends Component
{
    public User $salesManager;

    #[Url]
    public string $city = '';

    /**
     * IDs of the franchise holders ticked in the city currently being shown.
     *
     * @var array<int, string>
     */
    public array $selected = [];

    public function mount(User $salesManager): void
    {
        abort_unless($salesManager->role === UserRole::SalesManager, 404);

        $this->salesManager = $salesManager;
        $this->loadCurrentAssignments();
    }

    public function updatedCity(): void
    {
        $this->loadCurrentAssignments();
    }

    public function selectAll(): void
    {
        $this->selected = $this->holdersInCity()->pluck('id')->map(fn (int $id) => (string) $id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    /**
     * Assign the ticked franchise holders in this city to the sales manager and release
     * any in the same city that were unticked. Other cities are never touched.
     */
    public function save(): void
    {
        if ($this->city === '') {
            return;
        }

        $holdersInCity = $this->holdersInCity()->get();
        $selectedIds = collect($this->selected)->map(fn ($id) => (int) $id);

        $toAssign = $holdersInCity->whereIn('id', $selectedIds)->pluck('id');
        $toRelease = $holdersInCity
            ->where('sales_manager_id', $this->salesManager->id)
            ->whereNotIn('id', $selectedIds)
            ->pluck('id');

        User::query()->whereIn('id', $toAssign)->update(['sales_manager_id' => $this->salesManager->id]);
        User::query()->whereIn('id', $toRelease)->update(['sales_manager_id' => null]);

        $this->loadCurrentAssignments();

        Flux::toast(variant: 'success', text: __(':assigned assigned, :released removed.', [
            'assigned' => $toAssign->count(),
            'released' => $toRelease->count(),
        ]));
    }

    public function release(int $holderId): void
    {
        $this->franchiseHolders()
            ->where('sales_manager_id', $this->salesManager->id)
            ->whereKey($holderId)
            ->update(['sales_manager_id' => null]);

        $this->loadCurrentAssignments();

        Flux::toast(variant: 'success', text: __('Franchise removed from :name.', ['name' => $this->salesManager->name]));
    }

    /**
     * @return Builder<User>
     */
    private function franchiseHolders(): Builder
    {
        return User::query()->where('role', UserRole::FranchiseHolder);
    }

    /**
     * @return Builder<User>
     */
    private function holdersInCity(): Builder
    {
        return $this->franchiseHolders()->whereRaw('lower(trim(city)) = ?', [mb_strtolower(trim($this->city))]);
    }

    private function loadCurrentAssignments(): void
    {
        $this->selected = $this->city === ''
            ? []
            : $this->holdersInCity()
                ->where('sales_manager_id', $this->salesManager->id)
                ->pluck('id')
                ->map(fn (int $id) => (string) $id)
                ->all();
    }

    /**
     * Distinct cities of all franchise holders, keyed by lower-case name so "pune" and "Pune" count as one.
     *
     * @return Collection<string, string>
     */
    private function cities(): Collection
    {
        return $this->franchiseHolders()
            ->whereNotNull('city')
            ->pluck('city')
            ->map(fn (string $city) => trim($city))
            ->filter()
            ->groupBy(fn (string $city) => mb_strtolower($city))
            ->map(fn (Collection $variants) => $variants->first())
            ->sortKeys();
    }

    public function with(): array
    {
        return [
            'cities' => $this->cities(),
            'holders' => $this->city === ''
                ? collect()
                : $this->holdersInCity()->with('salesManager')->orderBy('name')->get(),
            'assigned' => $this->salesManager->franchiseHolders()->orderBy('city')->orderBy('name')->get(),
        ];
    }
}; ?>

<section class="w-full max-w-4xl">
    <flux:button variant="ghost" :href="route('admin.sales-managers.index')" wire:navigate icon="arrow-left">
        {{ __('Back to sales managers') }}
    </flux:button>

    <div class="mt-4">
        <flux:heading size="xl">{{ __('Assign franchises to :name', ['name' => $salesManager->name]) }}</flux:heading>
        <flux:text class="mt-1">
            {{ __('Choose a city, then tick the franchises this sales manager should look after. You can assign as many as you like.') }}
        </flux:text>
    </div>

    <flux:card class="mt-6">
        <flux:select wire:model.live="city" :label="__('1. Choose a city')" class="max-w-sm">
            <flux:select.option value="">{{ __('Select a city...') }}</flux:select.option>
            @foreach ($cities as $key => $label)
                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        @if ($city !== '')
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <flux:heading size="lg">{{ __('2. Select franchises in :city', ['city' => $cities[mb_strtolower(trim($city))] ?? $city]) }}</flux:heading>
                <div class="flex gap-2">
                    <flux:button size="sm" variant="ghost" wire:click="selectAll">{{ __('Select all') }}</flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="clearSelection">{{ __('Clear') }}</flux:button>
                </div>
            </div>

            @if ($holders->isEmpty())
                <flux:text class="mt-4">{{ __('No franchises found in this city.') }}</flux:text>
            @else
                <div class="mt-3 divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($holders as $holder)
                        <label wire:key="holder-{{ $holder->id }}" class="flex cursor-pointer items-center gap-4 px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <flux:checkbox wire:model="selected" value="{{ $holder->id }}" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $holder->business_name ?: $holder->name }}</p>
                                <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $holder->name }} &middot; {{ $holder->email }}
                                </p>
                            </div>

                            @if ($holder->sales_manager_id && $holder->sales_manager_id !== $salesManager->id)
                                <flux:badge size="sm" color="amber">{{ __('Currently with :name', ['name' => $holder->salesManager?->name]) }}</flux:badge>
                            @elseif ($holder->sales_manager_id === $salesManager->id)
                                <flux:badge size="sm" color="green">{{ __('Assigned') }}</flux:badge>
                            @else
                                <flux:badge size="sm">{{ __('Unassigned') }}</flux:badge>
                            @endif
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 flex items-center gap-4">
                    <flux:button variant="primary" wire:click="save">
                        {{ __('Save assignments (:count selected)', ['count' => count($selected)]) }}
                    </flux:button>
                    <flux:text size="sm">{{ __('Ticking a franchise that belongs to another sales manager moves it to this one.') }}</flux:text>
                </div>
            @endif
        @endif
    </flux:card>

    <flux:heading size="lg" class="mt-10">{{ __('Currently assigned (:count)', ['count' => $assigned->count()]) }}</flux:heading>

    <flux:table class="mt-4">
        <flux:table.columns>
            <flux:table.column>{{ __('Franchise') }}</flux:table.column>
            <flux:table.column>{{ __('City') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($assigned as $holder)
                <flux:table.row wire:key="assigned-{{ $holder->id }}">
                    <flux:table.cell>
                        {{ $holder->business_name ?: $holder->name }}
                        <flux:text size="sm" class="text-zinc-500">{{ $holder->name }}</flux:text>
                    </flux:table.cell>
                    <flux:table.cell>{{ $holder->city }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm">{{ $holder->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>
                        <flux:button size="sm" variant="ghost" icon="x-mark" wire:click="release({{ $holder->id }})" wire:confirm="{{ __('Remove this franchise from :name?', ['name' => $salesManager->name]) }}">
                            {{ __('Remove') }}
                        </flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __('No franchises assigned yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
