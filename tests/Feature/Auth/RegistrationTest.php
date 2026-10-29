<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;

/**
 * A package that can be bought: active, with a product that is in stock.
 */
function buyablePackage(string $slug = 'basic', string $name = 'Basic'): Package
{
    $package = Package::factory()->create(['slug' => $slug, 'name' => $name, 'price' => 5000, 'fixed_price' => 5000]);
    $package->products()->attach(Product::factory()->create(['stock' => 100]), ['quantity' => 2]);

    return $package;
}

function packageSignup(array $overrides = []): array
{
    return [
        'name' => 'Test Owner',
        'email' => 'owner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '9876543210',
        'business_name' => 'Green Leaf Franchise',
        'address' => '123 Main St',
        'city' => 'Nagpur',
        'state' => 'Maharashtra',
        'pincode' => '440001',
        'package' => 'basic',
        ...$overrides,
    ];
}

// ---- Registration needs a package -----------------------------------------

test('the register page sends visitors without a package to choose one', function () {
    buyablePackage();

    $this->get(route('register'))
        ->assertRedirect(route('packages.index'))
        ->assertSessionHas('status', 'Registration is part of buying a package. Choose a package to get started.');
});

test('the register page also redirects for an unknown, inactive or out of stock package', function () {
    $inactive = buyablePackage('mini', 'Mini');
    $inactive->update(['is_active' => false]);

    $empty = Package::factory()->create(['slug' => 'super', 'name' => 'Super']);
    $empty->products()->attach(Product::factory()->create(['stock' => 0]), ['quantity' => 1]);

    $this->get(route('register', ['package' => 'nope']))->assertRedirect(route('packages.index'));
    $this->get(route('register', ['package' => 'mini']))->assertRedirect(route('packages.index'));
    $this->get(route('register', ['package' => 'super']))
        ->assertRedirect(route('packages.index'))
        ->assertSessionHas('status', 'The Super package is out of stock right now. Please choose another package.');
});

test('the register page opens for a package that can be bought', function () {
    buyablePackage();

    $this->get(route('register', ['package' => 'basic']))->assertOk()->assertSee('Register to buy the Basic package');
});

test('the registration form uses a wide card while login keeps the compact one', function () {
    buyablePackage();

    $this->get(route('register', ['package' => 'basic']))->assertOk()->assertSee('flex-col gap-2 max-w-xl', false);
    $this->get(route('login'))->assertOk()->assertSee('flex-col gap-2 max-w-sm', false);
});

test('fields in the registration form do not stretch to the height of their neighbour', function () {
    buyablePackage();

    $html = $this->get(route('register', ['package' => 'basic']))->assertOk()->getContent();

    // Side-by-side fields (passwords, city/state/pincode) must align to the top so an error
    // message under one cannot push its neighbour's input down.
    expect(substr_count($html, 'items-start'))->toBeGreaterThanOrEqual(2);
});

test('the franchise name is asked for right after the full name', function () {
    buyablePackage();

    $this->get(route('register', ['package' => 'basic']))
        ->assertOk()
        ->assertSeeInOrder(['Full name', 'Franchise name', 'Email address', 'Password']);
});

test('submitting the form without a package is refused', function () {
    buyablePackage();

    $this->post(route('register.store'), packageSignup(['package' => '']))->assertSessionHasErrors(['package' => 'Please choose a package to register.']);

    expect(User::where('email', 'owner@example.com')->exists())->toBeFalse();

    $this->assertGuest();
});

test('submitting the form with a package that does not exist or is inactive is refused', function () {
    $inactive = buyablePackage('mini', 'Mini');
    $inactive->update(['is_active' => false]);

    $this->post(route('register.store'), packageSignup(['package' => 'nope']))->assertSessionHasErrors('package');
    $this->post(route('register.store'), packageSignup(['package' => 'mini']))->assertSessionHasErrors('package');

    expect(User::where('email', 'owner@example.com')->exists())->toBeFalse();
});

test('submitting the form for a package that is out of stock is refused', function () {
    $package = buyablePackage();
    $package->products->first()->update(['stock' => 0]);

    $this->post(route('register.store'), packageSignup())->assertSessionHasErrors(['package' => 'That package is out of stock right now.']);

    expect(User::where('email', 'owner@example.com')->exists())->toBeFalse();
});

test('the franchise name is required and saved on the account', function () {
    buyablePackage();

    $this->post(route('register.store'), packageSignup(['business_name' => '']))->assertSessionHasErrors('business_name');
    expect(User::where('email', 'owner@example.com')->exists())->toBeFalse();

    $this->post(route('register.store'), packageSignup());

    expect(User::where('email', 'owner@example.com')->firstOrFail()->business_name)->toBe('Green Leaf Franchise');
});

test('registering for a package creates a pending franchise holder who goes straight to payment', function () {
    $package = buyablePackage();

    $response = $this->post(route('register.store'), packageSignup());

    $user = User::where('email', 'owner@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::FranchiseHolder)
        ->and($user->status)->toBe(UserStatus::Pending)
        ->and($user->signup_package_id)->toBe($package->id);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('packages.checkout', $package));
});

// ---- No register buttons anywhere -----------------------------------------

test('no public page offers a register button or link', function () {
    buyablePackage();

    foreach (['home', 'franchise', 'login', 'products.index'] as $page) {
        $this->get(route($page))->assertOk()->assertDontSee(route('register'), false);
    }

    $product = Product::factory()->create();
    $this->get(route('products.show', $product))->assertOk()->assertDontSee(route('register'), false);
});

test('the only way to the register form is selecting a package', function () {
    buyablePackage();

    $html = $this->get(route('packages.index'))->assertOk()->getContent();

    // There is no plain register link, only the per package "Select" links.
    expect($html)->not->toContain('href="'.route('register').'"')
        ->toContain(route('register', ['package' => 'basic']));
});

test('the menus point visitors to the packages instead of registering', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('>Register<', false)
        ->assertSee(route('packages.index'), false);

    $this->get(route('login'))->assertOk()->assertSee('View franchise packages')->assertDontSee('Register as a franchise holder');
});

// ---- Accounts waiting for approval -----------------------------------------

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
