<?php

use App\Enums\UserStatus;
use App\Models\User;

test('the sales team has their own login page', function () {
    $this->get(route('sales.login'))
        ->assertOk()
        ->assertSee('Sales team login')
        ->assertSee('name="portal" value="sales"', false)
        ->assertSee(route('login.store'), false)
        ->assertDontSee('Register as a franchise holder');

    expect(route('sales.login', absolute: false))->toBe('/sales/login');
});

test('a sales manager can log in from the sales login page and lands on the sales dashboard', function () {
    $manager = User::factory()->salesManager()->create();

    $this->post(route('login.store'), ['email' => $manager->email, 'password' => 'password', 'portal' => 'sales'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($manager);

    $this->get(route('dashboard'))->assertRedirect(route('sales.dashboard'));
});

test('a sales manager is sent to the sales login page when they try the main login', function () {
    $manager = User::factory()->salesManager()->create();

    $this->post(route('login.store'), ['email' => $manager->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Sales managers log in from the sales team login page: '.route('sales.login')]);

    $this->assertGuest();
});

test('a franchise holder cannot log in from the sales login page', function () {
    $holder = User::factory()->create();

    $this->post(route('login.store'), ['email' => $holder->email, 'password' => 'password', 'portal' => 'sales'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('an admin cannot log in from the sales login page', function () {
    $admin = User::factory()->admin()->create();

    $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password', 'portal' => 'sales'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('the sales login gives the same generic error for a wrong password as the main login', function () {
    $manager = User::factory()->salesManager()->create();

    $this->post(route('login.store'), ['email' => $manager->email, 'password' => 'wrong', 'portal' => 'sales'])
        ->assertSessionHasErrors('email');

    $salesError = session('errors')->first('email');

    $this->post(route('login.store'), ['email' => $manager->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe($salesError)
        ->and($salesError)->not->toContain('Sales managers');
});

test('a suspended sales manager cannot log in from the sales login page', function () {
    $manager = User::factory()->salesManager()->create(['status' => UserStatus::Suspended]);

    $this->post(route('login.store'), ['email' => $manager->email, 'password' => 'password', 'portal' => 'sales'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('logged out visitors to the sales area are sent to the sales login page', function () {
    $this->get(route('sales.dashboard'))->assertRedirect(route('sales.login'));
    $this->get(route('sales.franchise-holders.index'))->assertRedirect(route('sales.login'));
});

test('logged out visitors to other areas still go to the main login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get(route('orders.index'))->assertRedirect(route('login'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a logged in user visiting the sales login page is sent on to their dashboard', function () {
    $this->actingAs(User::factory()->salesManager()->create())
        ->get(route('sales.login'))
        ->assertRedirect();
});

test('the main login page points sales managers to their own login', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('sales.login'), false)
        ->assertDontSee('name="portal"', false);
});
