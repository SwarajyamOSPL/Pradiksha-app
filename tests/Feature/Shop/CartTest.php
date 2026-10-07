<?php

use App\Models\Product;
use App\Models\User;

test('guests cannot access the cart', function () {
    $this->get(route('cart.index'))->assertRedirect(route('login'));
});

test('pending franchise holders cannot access the cart', function () {
    $user = User::factory()->pending()->create();

    $this->actingAs($user)->get(route('cart.index'))->assertRedirect(route('account.pending'));
});

test('an approved franchise holder can add a product to the cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])
        ->assertRedirect();

    expect(session('cart'))->toBe([$product->id => 2]);
});

test('adding an inactive product to the cart 404s', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->post(route('cart.add'), ['product_id' => $product->id])
        ->assertNotFound();
});

test('an approved franchise holder can update the quantity of a cart item', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($user)
        ->patch(route('cart.update', $product), ['quantity' => 5])
        ->assertRedirect();

    expect(session('cart'))->toBe([$product->id => 5]);
});

test('setting the quantity to zero removes the item from the cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($user)->patch(route('cart.update', $product), ['quantity' => 0]);

    expect(session('cart'))->toBe([]);
});

test('an approved franchise holder can remove an item from the cart', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($user)
        ->delete(route('cart.destroy', $product))
        ->assertRedirect();

    expect(session('cart'))->toBe([]);
});

test('the cart page lists items and their total', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Cart Test Product', 'price' => 150]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Cart Test Product')
        ->assertSee('450.00');
});
