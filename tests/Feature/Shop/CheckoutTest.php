<?php

use App\Enums\OrderType;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('checkout redirects to the cart when it is empty', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->assertRedirect(route('cart.index'));
});

test('placing a custom order creates an order from the cart and clears it', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['price' => 100]);
    $productB = Product::factory()->create(['price' => 50]);

    session(['cart' => [$productA->id => 2, $productB->id => 3]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->set('notes', 'Please deliver in the morning')
        ->call('placeOrder');

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect($order->type)->toBe(OrderType::Custom);
    expect($order->package_id)->toBeNull();
    expect((float) $order->total_amount)->toBe(350.0);
    expect($order->notes)->toBe('Please deliver in the morning');
    expect($order->items)->toHaveCount(2);

    expect(session('cart', []))->toBe([]);
});

test('placing a package order uses the package price, not the sum of product prices', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['price' => 999]);
    $product = Product::factory()->create(['price' => 5000]);
    $package->products()->attach($product, ['quantity' => 3]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $package])
        ->call('placeOrder');

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect($order->type)->toBe(OrderType::Package);
    expect($order->package_id)->toBe($package->id);
    expect((float) $order->total_amount)->toBe(999.0);

    $item = $order->items->first();
    expect($item->quantity)->toBe(3);
    expect((float) $item->unit_price)->toBe(5000.0);
});

test('an inactive package cannot be checked out', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['is_active' => false]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $package])
        ->assertNotFound();
});
