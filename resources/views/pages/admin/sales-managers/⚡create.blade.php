<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Add Sales Manager')] class extends Component
{
    use PasswordValidationRules, ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save(): void
    {
        $validated = $this->validate([
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::SalesManager,
            'status' => UserStatus::Approved,
        ]);

        Flux::toast(variant: 'success', text: __('Sales manager created.'));

        $this->redirectRoute('admin.sales-managers.index', navigate: true);
    }
}; ?>

<section class="w-full max-w-lg">
    <flux:heading size="xl">{{ __('Add Sales Manager') }}</flux:heading>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="password" :label="__('Password')" type="password" required viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required viewable />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.sales-managers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
