<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new franchise holders can register but start pending approval', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test Franchise',
        'email' => 'franchise@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '9876543210',
        'business_name' => 'Test Shop',
        'address' => '123 Main St',
        'city' => 'Nagpur',
        'state' => 'Maharashtra',
        'pincode' => '440001',
    ]);

    $user = User::where('email', 'franchise@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::FranchiseHolder);
    expect($user->status)->toBe(UserStatus::Pending);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('pending franchise holders are redirected away from the dashboard', function () {
    $user = User::factory()->pending()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertRedirect(route('account.pending'));
});

test('approved franchise holders can reach the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
});
