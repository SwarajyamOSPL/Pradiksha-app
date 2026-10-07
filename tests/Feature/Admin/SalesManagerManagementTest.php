<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('non-admins cannot access the sales managers index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.sales-managers.index'))->assertForbidden();
});

test('admin can create a sales manager', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.create')
        ->set('name', 'New Manager')
        ->set('email', 'manager@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertHasNoErrors();

    $manager = User::where('email', 'manager@example.com')->firstOrFail();

    expect($manager->role)->toBe(UserRole::SalesManager);
    expect($manager->status)->toBe(UserStatus::Approved);
    expect(Hash::check('password', $manager->password))->toBeTrue();
});

test('admin can edit a sales manager without changing the password', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();
    $originalPassword = $manager->password;

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.edit', ['salesManager' => $manager])
        ->set('name', 'Updated Manager')
        ->call('save')
        ->assertHasNoErrors();

    $manager->refresh();

    expect($manager->name)->toBe('Updated Manager');
    expect($manager->password)->toBe($originalPassword);
});

test('admin can toggle a sales managers status', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.index')
        ->call('toggleStatus', $manager->id);

    expect($manager->fresh()->status)->toBe(UserStatus::Suspended);
});
