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

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(User $salesManager): void
    {
        abort_unless($salesManager->role === UserRole::SalesManager, 404);

        $this->salesManager = $salesManager;
        $this->name = $salesManager->name;
        $this->email = $salesManager->email;
    }

    public function save(): void
    {
        $validated = $this->validate([
            ...$this->profileRules($this->salesManager->id),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
        ]);

        $this->salesManager->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...(filled($validated['password']) ? ['password' => $validated['password']] : []),
        ]);

        Flux::toast(variant: 'success', text: __('Sales manager updated.'));

        $this->redirectRoute('admin.sales-managers.index', navigate: true);
    }
}; ?>

<section class="w-full max-w-lg">
    <flux:heading size="xl">{{ __('Edit Sales Manager') }}</flux:heading>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="password" :label="__('New password')" type="password" description="{{ __('Leave blank to keep the current password.') }}" viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm new password')" type="password" viewable />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.sales-managers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
