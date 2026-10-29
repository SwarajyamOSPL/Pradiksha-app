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

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save(): void
    {
        $validated = $this->validate([
            ...$this->profileRules(),
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'password' => $this->passwordRules(),
        ]);

        $manager = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => UserRole::SalesManager,
            'status' => UserStatus::Approved,
        ]);

        Flux::toast(variant: 'success', text: __('Sales manager created. Now assign their franchises.'));

        $this->redirectRoute('admin.sales-managers.franchises', $manager, navigate: true);
    }
}; ?>

<section class="w-full max-w-lg">
    <flux:heading size="xl">{{ __('Add Sales Manager') }}</flux:heading>

    <form wire:submit="save" class="mt-6 flex flex-col gap-6">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
        <flux:input wire:model="email" :label="__('Email')" type="email" required />
        <flux:input wire:model="phone" :label="__('Contact number')" type="tel" required description="{{ __('Shown to the franchise holders this sales manager looks after.') }}" />
        <flux:input wire:model="password" :label="__('Password')" type="password" required viewable />
        <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required viewable />

        <div class="flex items-center gap-4">
            <flux:button variant="primary" type="submit">{{ __('Create') }}</flux:button>
            <flux:button variant="ghost" :href="route('admin.sales-managers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
        </div>
    </form>
</section>
