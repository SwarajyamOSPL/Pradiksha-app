<?php

use App\Enums\DiscountType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    config([
        'services.razorpay.key_id' => 'rzp_test_key',
        'services.razorpay.key_secret' => 'test_secret',
        'invoice.tax.rate' => 0,
    ]);

    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123']),
    ]);
});

function razorpaySignature(string $razorpayOrderId, string $paymentId, string $secret = 'test_secret'): string
{
    return hash_hmac('sha256', $razorpayOrderId.'|'.$paymentId, $secret);
}

test('checkout redirects to the cart when it is empty', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->assertRedirect(route('cart.index'));
});

test('paying for a basket creates an unpaid order and opens the payment window', function () {
    $user = User::factory()->create();
    $productA = Product::factory()->create(['price' => 100]);
    $productB = Product::factory()->create(['price' => 50]);

    session(['cart' => [$productA->id => 2, $productB->id => 3]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->set('notes', 'Please deliver in the morning')
        ->call('pay')
        ->assertDispatched('start-razorpay');

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect($order->type)->toBe(OrderType::Custom)
        ->and($order->package_id)->toBeNull()
        ->and((float) $order->total_amount)->toBe(350.0)
        ->and($order->notes)->toBe('Please deliver in the morning')
        ->and($order->items)->toHaveCount(2)
        ->and($order->payment_status)->toBe(PaymentStatus::Pending)
        ->and($order->razorpay_order_id)->toBe('order_TEST123')
        ->and($order->invoice_number)->toBeNull();

    // The basket is only emptied once the payment succeeds.
    expect(session('cart'))->not->toBe([]);

    Http::assertSent(fn ($request) => $request['amount'] === 35000 && $request['currency'] === 'INR');
});

test('a verified payment marks the order paid, issues an invoice, emails the customer and notifies admins', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 200]);
    session(['cart' => [$product->id => 2]]);

    $component = Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay');

    $order = Order::where('user_id', $user->id)->firstOrFail();

    $component
        ->call('confirmPayment', [
            'razorpay_order_id' => 'order_TEST123',
            'razorpay_payment_id' => 'pay_ABC',
            'razorpay_signature' => razorpaySignature('order_TEST123', 'pay_ABC'),
        ])
        ->assertRedirect(route('orders.show', $order));

    $order->refresh();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->status)->toBe(OrderStatus::Confirmed)
        ->and($order->razorpay_payment_id)->toBe('pay_ABC')
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->invoice_number)->toBe(sprintf('INV-%s-%06d', now()->format('Y'), $order->id));

    expect(session('cart', []))->toBe([]);

    Mail::assertSent(OrderConfirmationMail::class, fn (OrderConfirmationMail $mail) => $mail->hasTo($user->email) && $mail->order->is($order));

    expect($admin->unreadNotifications)->toHaveCount(1)
        ->and($admin->unreadNotifications->first()->type)->toBe(NewOrderPlaced::class)
        ->and($admin->unreadNotifications->first()->data['order_id'])->toBe($order->id);
});

test('a payment with a forged signature is rejected and the order stays unpaid', function () {
    Mail::fake();

    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 200]);
    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay')
        ->call('confirmPayment', [
            'razorpay_order_id' => 'order_TEST123',
            'razorpay_payment_id' => 'pay_ABC',
            'razorpay_signature' => razorpaySignature('order_TEST123', 'pay_ABC', 'wrong_secret'),
        ])
        ->assertNoRedirect();

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect($order->payment_status)->toBe(PaymentStatus::Pending)
        ->and($order->invoice_number)->toBeNull();

    expect(session('cart'))->not->toBe([]);

    Mail::assertNothingSent();
});

test('a customer cannot confirm payment against someone elses order', function () {
    Mail::fake();

    $victim = User::factory()->create();
    $victimOrder = Order::factory()->create([
        'user_id' => $victim->id,
        'razorpay_order_id' => 'order_TEST123',
        'total_amount' => 500,
    ]);

    $attacker = User::factory()->create();
    $product = Product::factory()->create(['price' => 100]);
    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($attacker)
        ->test('pages::shop.checkout')
        ->set('orderId', $victimOrder->id)
        ->call('confirmPayment', [
            'razorpay_order_id' => 'order_TEST123',
            'razorpay_payment_id' => 'pay_ABC',
            'razorpay_signature' => razorpaySignature('order_TEST123', 'pay_ABC'),
        ]);

    expect($victimOrder->refresh()->payment_status)->toBe(PaymentStatus::Pending);

    Mail::assertNothingSent();
});

test('confirming the same payment twice only sends one email and one notification', function () {
    Mail::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'razorpay_order_id' => 'order_TEST123',
        'total_amount' => 500,
    ]);

    $response = [
        'razorpay_order_id' => 'order_TEST123',
        'razorpay_payment_id' => 'pay_ABC',
        'razorpay_signature' => razorpaySignature('order_TEST123', 'pay_ABC'),
    ];

    Livewire::actingAs($user)
        ->test('pages::shop.orders.show', ['order' => $order])
        ->call('confirmPayment', $response)
        ->call('confirmPayment', $response);

    Mail::assertSent(OrderConfirmationMail::class, 1);
    expect($admin->unreadNotifications)->toHaveCount(1);
});

test('payment is not started when razorpay is not configured', function () {
    config(['services.razorpay.key_id' => null, 'services.razorpay.key_secret' => null]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100]);
    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay')
        ->assertNotDispatched('start-razorpay');

    Http::assertNothingSent();
});

test('a basket with no priced products cannot be paid for', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => null]);
    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay')
        ->assertNotDispatched('start-razorpay');

    expect(Order::where('user_id', $user->id)->exists())->toBeFalse();

    Http::assertNothingSent();
});

test('retrying payment reuses the same order and razorpay order', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100]);
    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay')
        ->call('pay')
        ->assertDispatched('start-razorpay');

    expect(Order::where('user_id', $user->id)->count())->toBe(1);

    Http::assertSentCount(1);
});

test('gst added on top of prices is included in the payable total', function () {
    config(['invoice.tax.rate' => 12, 'invoice.tax.prices_include_tax' => false]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 1000]);
    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay');

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect((float) $order->subtotal)->toBe(1000.0)
        ->and((float) $order->tax_amount)->toBe(120.0)
        ->and((float) $order->total_amount)->toBe(1120.0)
        ->and($order->tax_inclusive)->toBeFalse();

    Http::assertSent(fn ($request) => $request['amount'] === 112000);
});

test('gst already included in prices does not change the payable total', function () {
    config(['invoice.tax.rate' => 12, 'invoice.tax.prices_include_tax' => true]);

    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 1120]);
    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->call('pay');

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect((float) $order->total_amount)->toBe(1120.0)
        ->and((float) $order->tax_amount)->toBe(120.0)
        ->and($order->tax_inclusive)->toBeTrue();
});

test('paying for a package charges the discounted package price', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['discount_type' => DiscountType::Percent, 'discount_value' => 10]);
    $productA = Product::factory()->create(['price' => 100]);
    $productB = Product::factory()->create(['price' => 50]);
    $package->products()->attach([
        $productA->id => ['quantity' => 3],
        $productB->id => ['quantity' => 2],
    ]);
    $package->refreshPrice();

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $package])
        ->set('notes', 'Gift wrap')
        ->call('pay')
        ->assertDispatched('start-razorpay');

    $order = Order::where('user_id', $user->id)->firstOrFail();

    // Products total 3 x 100 + 2 x 50 = 400, less 10% = 360.
    expect($order->type)->toBe(OrderType::Package)
        ->and($order->package_id)->toBe($package->id)
        ->and((float) $order->subtotal)->toBe(400.0)
        ->and((float) $order->discount_amount)->toBe(40.0)
        ->and((float) $order->total_amount)->toBe(360.0)
        ->and($order->items)->toHaveCount(2)
        ->and($order->items->firstWhere('product_id', $productA->id)->quantity)->toBe(3)
        ->and((float) $order->items->firstWhere('product_id', $productA->id)->unit_price)->toBe(100.0);

    Http::assertSent(fn ($request) => $request['amount'] === 36000);
});

test('an inactive package cannot be checked out', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['is_active' => false]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $package])
        ->assertNotFound();
});
