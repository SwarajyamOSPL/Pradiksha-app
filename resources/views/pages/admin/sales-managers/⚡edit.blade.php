<?php

use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Edit Sales Manager')] class extends Component
{
    use ProfileValidationRules;

    public User $salesManager;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $salesManager): void
    {
        abort_unless($salesManager->role === UserRole::SalesManager, 404);

        $this->salesManager = $salesManager;
        $this->name = $salesManager->name;
        $this->email = $salesManager->email;
        $this->phone = (string) $salesManager->phone;
    }

    public function save(): void
    {
        $validated = $this->validate([
            ...$this->profileRules($this->salesManager->id),
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
        ]);

        $this->salesManager->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            ...(filled($validated['password']) ? ['password' => $validated['password']] : []),
        ]);

        Flux::toast(variant: 'success', text: __('Sales manager updated.'));

        $this->redirectRoute('admin.sales-managers.index', navigate: true);
    }
}; ?>

<section class="w-full max-w-lg">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <flux:heading size="xl">{{ __('Edit Sales Manager') }}</flux:heading>
        <flux:button size="sm" icon="users" :href="route('admin.sales-managers.franchises', $salesManager)" wire:navigate>
            {{ __('Assign franchises') }}
        </flux:button>
    </div>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="phone" :label="__('Contact number')" type="tel" required description="{{ __('Shown to the franchise holders this sales manager looks after.') }}" />
        <flux:input wire:model="password" :label="__('New password')" type="password" description="{{ __('Leave blank to keep the current password.') }}" viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm new password')" type="password" viewable />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.sales-managers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
