<?php

use App\Models\Package;
use App\Models\Product;
use App\Models\User;

test('home page loads and shows featured products', function () {
    $product = Product::factory()->create(['name' => 'Featured Herbal Oil']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Featured Herbal Oil');
});

test('guests can browse products but do not see prices', function () {
    $product = Product::factory()->create(['name' => 'Guest Test Product', 'price' => 499]);

    $this->get(route('products.index'))
        ->assertOk()
        ->assertSee('Guest Test Product')
        ->assertSee('Login to see price')
        ->assertDontSee('499.00');
});

test('approved franchise holders see product prices', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Approved Test Product', 'price' => 499]);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('499.00');
});

test('pending franchise holders do not see product prices', function () {
    $user = User::factory()->pending()->create();
    $product = Product::factory()->create(['name' => 'Pending Test Product', 'price' => 499]);

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('Pending approval')
        ->assertDontSee('499.00');
});

test('products can be filtered by category', function () {
    Product::factory()->create(['name' => 'Oil Product', 'category' => 'Hair Oil']);
    Product::factory()->create(['name' => 'Syrup Product', 'category' => 'Herbal Syrup']);

    $this->get(route('products.index', ['category' => 'Hair Oil']))
        ->assertOk()
        ->assertSee('Oil Product')
        ->assertDontSee('Syrup Product');
});

test('inactive products 404 on the show page', function () {
    $product = Product::factory()->create(['is_active' => false]);

    $this->get(route('products.show', $product))->assertNotFound();
});

test('active product show page renders', function () {
    $product = Product::factory()->create(['name' => 'Detail Page Product']);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Detail Page Product');
});

test('packages index lists packages and hides prices from guests', function () {
    $package = Package::factory()->create(['name' => 'Wellness Bundle', 'price' => 1999]);

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Wellness Bundle')
        ->assertDontSee('1,999.00');
});

test('package show page lists included products', function () {
    $package = Package::factory()->create(['name' => 'Detail Bundle']);
    $product = Product::factory()->create(['name' => 'Bundled Product']);
    $package->products()->attach($product, ['quantity' => 2]);

    $this->get(route('packages.show', $package))
        ->assertOk()
        ->assertSee('Detail Bundle')
        ->assertSee('Bundled Product');
});

test('franchise page loads for guests with registration calls to action', function () {
    $this->get(route('franchise'))
        ->assertOk()
        ->assertSee('Franchise Model')
        ->assertSee('Become a Franchise Partner')
        ->assertSee(route('register'))
        ->assertSee(route('login'));
});

test('franchise page does not show registration prompts to logged in partners', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('franchise'))
        ->assertOk()
        ->assertSee('Browse Products')
        ->assertDontSee('Ready to become a franchise partner?');
});

test('franchise page is linked from the storefront navbar and footer', function () {
    $html = $this->get(route('home'))->assertOk()->getContent();

    expect(substr_count($html, route('franchise')))->toBeGreaterThanOrEqual(3);
});

test('home page shows the slider images as a carousel', function () {
    $html = $this->get(route('home'))->assertOk()->assertSee('aria-roledescription="carousel"', false)->getContent();

    foreach (['pradiksha-range', 'metabolicrange', 'therapy-range'] as $slide) {
        expect($html)->toContain("images/sliders/{$slide}.webp")
            ->and(public_path("images/sliders/{$slide}.webp"))->toBeFile();
    }
});

test('home page does not show the category tag cloud', function () {
    Product::factory()->create(['category' => 'Distinctive Tag Category']);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('products?category=', false);
});
