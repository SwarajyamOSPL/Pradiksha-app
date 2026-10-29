<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('the registration form uses a wide card while login keeps the compact one', function () {
    $this->get(route('register'))->assertOk()->assertSee('flex-col gap-2 max-w-xl', false);
    $this->get(route('login'))->assertOk()->assertSee('flex-col gap-2 max-w-sm', false);
});

test('fields in the registration form do not stretch to the height of their neighbour', function () {
    $html = $this->get(route('register'))->assertOk()->getContent();

    // Side-by-side fields (passwords, city/state/pincode) must align to the top so an error
    // message under one cannot push its neighbour's input down.
    expect(substr_count($html, 'items-start'))->toBeGreaterThanOrEqual(2);
});

test('the franchise name is asked for right after the full name', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSeeInOrder(['Full name', 'Franchise name', 'Email address', 'Password']);
});

test('the franchise name is required and saved on the account', function () {
    $details = [
        'name' => 'Test Owner',
        'email' => 'owner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '9876543210',
        'address' => '123 Main St',
        'city' => 'Nagpur',
        'state' => 'Maharashtra',
        'pincode' => '440001',
    ];

    $this->post(route('register.store'), $details)->assertSessionHasErrors('business_name');
    expect(User::where('email', 'owner@example.com')->exists())->toBeFalse();

    $this->post(route('register.store'), [...$details, 'business_name' => 'Green Leaf Franchise']);

    expect(User::where('email', 'owner@example.com')->firstOrFail()->business_name)->toBe('Green Leaf Franchise');
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

    // Signing up sends a request to the admin; the new user is not logged in until approved.
    $this->assertGuest();
    $response->assertRedirect(route('login'))->assertSessionHas('status');
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
