<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'services.razorpay.key_id' => 'rzp_test_key',
        'services.razorpay.key_secret' => 'test_secret',
        'invoice.tax.rate' => 0,
    ]);

    Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123'])]);
});

function paymentFor(string $razorpayOrderId = 'order_TEST123'): array
{
    return [
        'razorpay_order_id' => $razorpayOrderId,
        'razorpay_payment_id' => 'pay_ABC',
        'razorpay_signature' => hash_hmac('sha256', $razorpayOrderId.'|pay_ABC', 'test_secret'),
    ];
}

function orderWithItem(Product $product, int $quantity, array $attributes = []): Order
{
    $order = Order::factory()->create($attributes);

    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'unit_price' => 100,
        'quantity' => $quantity,
        'line_total' => 100 * $quantity,
    ]);

    return $order;
}

// ---- Admin: managing stock -------------------------------------------------

test('new products start with no stock until the admin sets it', function () {
    $product = Product::create(['name' => 'New Tonic', 'slug' => 'new-tonic']);

    expect($product->refresh()->stock)->toBe(0)->and($product->isInStock())->toBeFalse();
});

test('the admin product list shows a stock in hand column with each products stock', function () {
    $admin = User::factory()->admin()->create();
    Product::factory()->create(['name' => 'Herbal Oil', 'stock' => 37]);

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertOk()
        ->assertSee('Stock in hand')
        ->assertSee('value="37"', false);
});

test('admin can change a products stock from the list', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['stock' => 5]);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.index')
        ->call('updateStock', $product, '250');

    expect($product->refresh()->stock)->toBe(250);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.index')
        ->call('updateStock', $product, '0');

    expect($product->refresh()->stock)->toBe(0);
});

test('invalid stock values from the list are rejected', function (string $value) {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['stock' => 12]);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.index')
        ->call('updateStock', $product, $value);

    expect($product->refresh()->stock)->toBe(12);
})->with(['negative' => '-5', 'text' => 'abc', 'decimal' => '2.5', 'blank' => '', 'huge' => '99999999']);

test('the admin list can be filtered by stock level and warns about low and empty stock', function () {
    $admin = User::factory()->admin()->create();
    Product::factory()->create(['name' => 'Plenty Tonic', 'stock' => 80]);
    Product::factory()->create(['name' => 'Few Left Tonic', 'stock' => 4]);
    Product::factory()->create(['name' => 'Empty Tonic', 'stock' => 0]);

    $page = Livewire::actingAs($admin)->test('pages::admin.products.index');

    $page->assertSee('1 out of stock, 1 running low');

    $page->set('stockFilter', 'out')->assertSee('Empty Tonic')->assertDontSee('Plenty Tonic')->assertDontSee('Few Left Tonic');
    $page->set('stockFilter', 'low')->assertSee('Few Left Tonic')->assertDontSee('Empty Tonic')->assertDontSee('Plenty Tonic');
    $page->set('stockFilter', 'in')->assertSee('Plenty Tonic')->assertSee('Few Left Tonic')->assertDontSee('Empty Tonic');
});

test('the admin product forms ask for stock and require a valid number', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.products.create')
        ->set('name', 'Stocked Tonic')
        ->set('stock', '-1')
        ->call('save')
        ->assertHasErrors(['stock']);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.create')
        ->set('name', 'Stocked Tonic')
        ->set('stock', '60')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('slug', 'stocked-tonic')->firstOrFail();

    expect($product->stock)->toBe(60);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.edit', ['product' => $product])
        ->assertSet('stock', '60')
        ->set('stock', '75')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh()->stock)->toBe(75);
});

// ---- Franchise holder: seeing stock ---------------------------------------

test('approved franchise holders see the stock level on products', function () {
    Product::factory()->create(['name' => 'Plenty Tonic', 'stock' => 80]);
    Product::factory()->create(['name' => 'Few Left Tonic', 'stock' => 4]);
    Product::factory()->create(['name' => 'Empty Tonic', 'stock' => 0]);

    $this->actingAs(User::factory()->create())
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('In stock: 80')
        ->assertSee('Only 4 left')
        ->assertSee('Out of stock');
});

test('guests do not see stock levels', function () {
    Product::factory()->create(['stock' => 80]);

    $this->get(route('products.index'))->assertOk()->assertDontSee('In stock: 80');
});

test('the franchise dashboard products show stock too', function () {
    Product::factory()->create(['name' => 'Plenty Tonic', 'stock' => 80]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('In stock: 80');
});

test('an in stock product page offers the add to basket form limited to the stock', function () {
    $product = Product::factory()->create(['stock' => 7]);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Only 7 left')
        ->assertSee('Add to Basket')
        ->assertSee('max="7"', false);
});

test('an out of stock product page has no add to basket form', function () {
    $product = Product::factory()->create(['stock' => 0]);

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Currently out of stock')
        ->assertDontSee('Add to Basket');
});

// ---- Basket ---------------------------------------------------------------

test('an out of stock product cannot be added to the basket', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Empty Tonic', 'stock' => 0]);

    $this->actingAs($user)
        ->from(route('products.show', $product))
        ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1])
        ->assertSessionHasErrors(['quantity' => 'Empty Tonic is out of stock.']);

    expect(session('cart', []))->toBe([]);
});

test('adding more than is in stock sets the basket to the available amount', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Few Left Tonic', 'stock' => 4]);

    $this->actingAs($user)
        ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 10])
        ->assertSessionHas('status', 'Only 4 of Few Left Tonic are in stock, so your basket now has 4.');

    expect(session('cart'))->toBe([$product->id => 4]);
});

test('topping up the basket cannot go past the stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3]);
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3]);

    expect(session('cart'))->toBe([$product->id => 5]);
});

test('exactly the stock available can be added', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 5]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 5])->assertSessionHasNoErrors();

    expect(session('cart'))->toBe([$product->id => 5]);
});

test('updating a basket line past the stock sets it to the available amount', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 6]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
    $this->actingAs($user)->patch(route('cart.update', $product), ['quantity' => 50]);

    expect(session('cart'))->toBe([$product->id => 6]);
});

test('a basket line can still be removed after the product runs out', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 3]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
    $product->update(['stock' => 0]);

    $this->actingAs($user)->patch(route('cart.update', $product), ['quantity' => 1])->assertSessionHasErrors('quantity');
    $this->actingAs($user)->delete(route('cart.destroy', $product));

    expect(session('cart'))->toBe([]);
});

test('the basket page flags items that ran out and blocks checkout', function () {
    $user = User::factory()->create();
    $gone = Product::factory()->create(['name' => 'Gone Tonic', 'stock' => 3]);
    $short = Product::factory()->create(['name' => 'Short Tonic', 'stock' => 10]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $gone->id, 'quantity' => 2]);
    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $short->id, 'quantity' => 8]);

    $gone->update(['stock' => 0]);
    $short->update(['stock' => 5]);

    $this->actingAs($user)->get(route('cart.index'))
        ->assertOk()
        ->assertSee('Out of stock. Please remove it to continue.')
        ->assertSee('Only 5 in stock. Please lower the quantity.')
        ->assertSee('Some items are not available in the quantity you chose')
        ->assertDontSee(route('checkout'));
});

test('the basket page lets you check out when everything is in stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 20]);

    $this->actingAs($user)->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

    $this->actingAs($user)->get(route('cart.index'))->assertOk()->assertSee(route('checkout'));
});

// ---- Checkout and payment -------------------------------------------------

test('paying for a basket is blocked while an item is short and no order is created', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Short Tonic', 'price' => 100, 'stock' => 10]);

    session(['cart' => [$product->id => 8]]);
    $product->update(['stock' => 5]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->assertSee('Only 5 of Short Tonic are available (you need 8).')
        ->call('pay')
        ->assertNotDispatched('start-razorpay');

    expect(Order::where('user_id', $user->id)->exists())->toBeFalse();

    Http::assertNothingSent();
});

test('paying for a package is blocked while one of its products is out of stock', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();
    $inStock = Product::factory()->create(['price' => 100, 'stock' => 50]);
    $empty = Product::factory()->create(['name' => 'Empty Tonic', 'price' => 100, 'stock' => 0]);
    $package->products()->attach([$inStock->id => ['quantity' => 1], $empty->id => ['quantity' => 1]]);
    $package->refreshPrice();

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $package])
        ->assertSee('This package is not available right now')
        ->assertSee('Empty Tonic is out of stock.')
        ->call('pay')
        ->assertNotDispatched('start-razorpay');

    expect(Order::where('user_id', $user->id)->exists())->toBeFalse();
});

test('a package needs enough stock for the quantity it contains', function () {
    $package = Package::factory()->create();
    $product = Product::factory()->create(['stock' => 2]);
    $package->products()->attach($product, ['quantity' => 3]);

    expect($package->load('products')->isAvailable())->toBeFalse();

    $product->update(['stock' => 3]);

    expect($package->load('products')->isAvailable())->toBeTrue();
});

test('an unavailable package is marked out of stock and cannot be ordered from its page', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['name' => 'Festival Pack']);
    $product = Product::factory()->create(['name' => 'Empty Tonic', 'price' => 100, 'stock' => 0]);
    $package->products()->attach($product, ['quantity' => 1]);
    $package->refreshPrice();

    $this->actingAs($user)->get(route('packages.index'))->assertOk()->assertSee('Out of stock');

    $this->actingAs($user)
        ->get(route('packages.show', $package))
        ->assertOk()
        ->assertSee('Currently out of stock')
        ->assertSee('Empty Tonic is out of stock.')
        ->assertDontSee(route('packages.checkout', $package));
});

test('an available package can be ordered from its page', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();
    $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
    $package->products()->attach($product, ['quantity' => 2]);
    $package->refreshPrice();

    $this->actingAs($user)
        ->get(route('packages.show', $package))
        ->assertOk()
        ->assertSee(route('packages.checkout', $package))
        ->assertDontSee('Currently out of stock');
});

test('retrying payment for an unpaid order is blocked once its product runs out', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 0]);
    $order = orderWithItem($product, 2, ['user_id' => $user->id, 'total_amount' => 200]);

    Livewire::actingAs($user)
        ->test('pages::shop.orders.show', ['order' => $order])
        ->call('pay')
        ->assertNotDispatched('start-razorpay');

    Http::assertNothingSent();
});

test('stock is taken out when the payment is verified', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['price' => 100, 'stock' => 20]);
    $productB = Product::factory()->create(['price' => 50, 'stock' => 9]);

    session(['cart' => [$productA->id => 4, $productB->id => 9]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay')
        ->call('confirmPayment', paymentFor());

    expect($productA->refresh()->stock)->toBe(16)
        ->and($productB->refresh()->stock)->toBe(0)
        ->and($productB->isInStock())->toBeFalse();
});

test('stock is not taken before payment', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100, 'stock' => 20]);

    session(['cart' => [$product->id => 4]]);

    Livewire::actingAs($user)->test('pages::shop.checkout')->call('pay');

    expect($product->refresh()->stock)->toBe(20);
});

test('a package order takes the package quantities out of stock', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();
    $product = Product::factory()->create(['price' => 100, 'stock' => 30]);
    $package->products()->attach($product, ['quantity' => 3]);
    $package->refreshPrice();

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $package])
        ->call('pay')
        ->call('confirmPayment', paymentFor());

    expect($product->refresh()->stock)->toBe(27);
});

test('confirming the same payment twice only deducts stock once', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100, 'stock' => 10]);
    $order = orderWithItem($product, 4, ['user_id' => $user->id, 'razorpay_order_id' => 'order_TEST123', 'total_amount' => 400]);

    Livewire::actingAs($user)
        ->test('pages::shop.orders.show', ['order' => $order])
        ->call('confirmPayment', paymentFor())
        ->call('confirmPayment', paymentFor());

    expect($product->refresh()->stock)->toBe(6);
});

test('stock never goes below zero if the last units are paid for twice', function () {
    Log::spy();

    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 1]);
    $order = orderWithItem($product, 3, ['user_id' => $user->id, 'razorpay_order_id' => 'order_TEST123', 'total_amount' => 300]);

    Livewire::actingAs($user)
        ->test('pages::shop.orders.show', ['order' => $order])
        ->call('confirmPayment', paymentFor());

    expect($product->refresh()->stock)->toBe(0)
        ->and($order->refresh()->isPaid())->toBeTrue();

    Log::shouldHaveReceived('warning')->once();
});

test('after the last unit is bought the product shows as out of stock to everyone', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Last Tonic', 'price' => 100, 'stock' => 2]);

    session(['cart' => [$product->id => 2]]);

    Livewire::actingAs($user)->test('pages::shop.checkout')->call('pay')->call('confirmPayment', paymentFor());

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', $product))
        ->assertSee('Currently out of stock')
        ->assertDontSee('Add to Basket');
});

// ---- Cancelling and manual payment ----------------------------------------

test('cancelling a paid order puts its stock back and undoing it takes it out again', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $order = orderWithItem($product, 4, ['status' => OrderStatus::Confirmed, 'payment_status' => 'paid', 'paid_at' => now(), 'invoice_number' => 'INV-TEST-1']);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('status', OrderStatus::Cancelled->value)
        ->call('updateOrder');

    expect($product->refresh()->stock)->toBe(14);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order->refresh()])
        ->set('status', OrderStatus::Confirmed->value)
        ->call('updateOrder');

    expect($product->refresh()->stock)->toBe(10);
});

test('cancelling an unpaid order does not change stock', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $order = orderWithItem($product, 4);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('status', OrderStatus::Cancelled->value)
        ->call('updateOrder');

    expect($product->refresh()->stock)->toBe(10);
});

test('marking an order paid by hand takes its stock out', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $order = orderWithItem($product, 3);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('paymentStatus', 'paid')
        ->call('updateOrder');

    expect($product->refresh()->stock)->toBe(7);
});
