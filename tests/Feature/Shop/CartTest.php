<?php

use App\Models\Product;
use App\Models\User;
use App\Support\Cart;

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

test('adding the same product again increases its quantity', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id]);

    expect(session('cart'))->toBe([$product->id => 3]);
});

test('the shopper sees a confirmation on the product page after adding to the basket', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Herbal Hair Oil']);

    $this->actingAs($user)
        ->from(route('products.show', $product))
        ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
        ->assertRedirect(route('products.show', $product));

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Herbal Hair Oil added to your basket.');
});

test('an invalid quantity is explained to the shopper instead of failing silently', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)
        ->from(route('products.show', $product))
        ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 100000])
        ->assertSessionHasErrors('quantity');

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('quantity');

    expect(session('cart', []))->toBe([]);
});

test('quantities are limited to 999 when adding, updating and topping up', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5000]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1000])->assertSessionHasErrors('quantity');
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 999]);
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 5]);

    expect(session('cart'))->toBe([$product->id => 999]);

    $this->actingAs($user)->patch(route('cart.update', $product), ['quantity' => 1000])->assertSessionHasErrors('quantity');

    expect(session('cart'))->toBe([$product->id => 999]);
});

test('a non numeric or negative quantity is rejected', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 'abc'])->assertSessionHasErrors('quantity');
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 0])->assertSessionHasErrors('quantity');
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => -3])->assertSessionHasErrors('quantity');

    expect(session('cart', []))->toBe([]);
});

test('a product that is deactivated after being added drops out of the basket', function () {
    $user = User::factory()->create();
    $kept = Product::factory()->create(['name' => 'Kept Tonic', 'price' => 100]);
    $gone = Product::factory()->create(['name' => 'Retired Tonic', 'price' => 900]);

    $this->withSession(['cart' => [$kept->id => 1, $gone->id => 2]]);

    $gone->update(['is_active' => false]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Kept Tonic')
        ->assertDontSee('Retired Tonic')
        ->assertSee('100.00')
        ->assertDontSee('1,900.00');

    expect(session('cart'))->toBe([$kept->id => 1]);
});

test('a deleted product disappears from the basket and the basket count', function () {
    $user = User::factory()->create();
    $kept = Product::factory()->create();
    $deleted = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $kept->id, 'quantity' => 1]);
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $deleted->id, 'quantity' => 4]);

    $deleted->delete();

    expect(app(Cart::class)->count())->toBe(1);
});

test('the basket count in the menu matches what is in the basket', function () {
    $user = User::factory()->create();
    $a = Product::factory()->create();
    $b = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $a->id, 'quantity' => 2]);
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $b->id, 'quantity' => 3]);

    $this->actingAs($user)->get(route('products.index'))->assertOk()->assertSee('Basket (5)');
});

test('the basket total adds up every line', function () {
    $user = User::factory()->create();
    $a = Product::factory()->create(['price' => 199.50]);
    $b = Product::factory()->create(['price' => 80]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $a->id, 'quantity' => 2]);
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $b->id, 'quantity' => 3]);

    $this->actingAs($user)->get(route('cart.index'))->assertOk()->assertSee('639.00');
});

test('an unpriced product says price on request and blocks checkout from the basket page', function () {
    $user = User::factory()->create();
    $unpriced = Product::factory()->create(['name' => 'Mystery Tonic', 'price' => null]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $unpriced->id, 'quantity' => 1]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Price on request')
        ->assertDontSee(route('checkout'));
});

test('the basket page confirms an update and a removal', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

    $this->actingAs($user)->from(route('cart.index'))->patch(route('cart.update', $product), ['quantity' => 4]);
    $this->actingAs($user)->get(route('cart.index'))->assertSee('Basket updated.');

    $this->actingAs($user)->from(route('cart.index'))->delete(route('cart.destroy', $product));
    $this->actingAs($user)->get(route('cart.index'))->assertSee('Item removed from your basket.')->assertSee('Your basket is empty.');
});

test('guests cannot add items to a basket', function () {
    $product = Product::factory()->create();

    $this->post(route('cart.add'), ['product_id' => $product->id])->assertRedirect(route('login'));

    expect(session('cart', []))->toBe([]);
});
