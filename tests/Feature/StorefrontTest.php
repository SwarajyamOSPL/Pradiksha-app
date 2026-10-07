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
