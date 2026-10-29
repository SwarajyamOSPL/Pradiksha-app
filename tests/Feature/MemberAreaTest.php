<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\FranchisePackagesSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// ---- Products with offer prices and add to cart ---------------------------

test('the member products page lists available products with offer prices', function () {
    Product::factory()->create(['name' => 'Herbal Hair Oil', 'price' => 95, 'mrp' => 130]);
    Product::factory()->create(['name' => 'Hidden Tonic', 'price' => 50, 'is_active' => false]);

    $this->actingAs(User::factory()->create())
        ->get(route('member.products'))
        ->assertOk()
        ->assertSee('Herbal Hair Oil')
        ->assertSee('Offer price')
        ->assertSee('₹95.00')
        ->assertSee('MRP ₹130.00')
        ->assertSee('27% off')
        ->assertDontSee('Hidden Tonic');
});

test('the mrp and discount badge only show when the offer price is really lower', function () {
    Product::factory()->create(['name' => 'Same Price Oil', 'price' => 100, 'mrp' => 100]);
    Product::factory()->create(['name' => 'Higher Price Oil', 'price' => 150, 'mrp' => 120]);
    Product::factory()->create(['name' => 'No Mrp Oil', 'price' => 80, 'mrp' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('member.products'))
        ->assertOk()
        ->assertDontSee('MRP ₹')
        ->assertDontSee('% off');
});

test('product offer helpers work out the saving', function () {
    $offer = Product::factory()->make(['price' => 75, 'mrp' => 100]);
    $none = Product::factory()->make(['price' => 100, 'mrp' => 100]);

    expect($offer->hasOffer())->toBeTrue()
        ->and($offer->savingsPercentage())->toBe(25)
        ->and($none->hasOffer())->toBeFalse()
        ->and($none->savingsPercentage())->toBe(0);
});

test('each available product has an add to cart form limited to its stock', function () {
    $product = Product::factory()->create(['price' => 95, 'stock' => 6]);

    $this->actingAs(User::factory()->create())
        ->get(route('member.products'))
        ->assertOk()
        ->assertSee('Add to cart')
        ->assertSee('name="product_id" value="'.$product->id.'"', false)
        ->assertSee('max="6"', false)
        ->assertSee(route('cart.add'), false);
});

test('an out of stock product shows stock status and no add to cart button', function () {
    Product::factory()->create(['name' => 'Empty Tonic', 'price' => 95, 'stock' => 0]);

    $this->actingAs(User::factory()->create())
        ->get(route('member.products'))
        ->assertOk()
        ->assertSee('Out of stock')
        ->assertDontSee('Add to cart');
});

test('adding to the cart from the member page confirms it on the same page', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['name' => 'Herbal Hair Oil', 'price' => 95, 'stock' => 10]);

    $this->actingAs($user)
        ->from(route('member.products'))
        ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3])
        ->assertRedirect(route('member.products'));

    expect(session('cart'))->toBe([$product->id => 3]);

    $this->actingAs($user)
        ->get(route('member.products'))
        ->assertOk()
        ->assertSee('Herbal Hair Oil added to your basket.')
        ->assertSee('Cart (3)');
});

test('the member products page can be searched and limited to products in stock', function () {
    Product::factory()->create(['name' => 'Ortho Oil', 'price' => 100, 'stock' => 5]);
    Product::factory()->create(['name' => 'Onion Shampoo', 'price' => 100, 'stock' => 0]);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::shop.products')
        ->set('search', 'Ortho')
        ->assertSee('Ortho Oil')
        ->assertDontSee('Onion Shampoo')
        ->set('search', '')
        ->set('inStockOnly', true)
        ->assertSee('Ortho Oil')
        ->assertDontSee('Onion Shampoo');
});

test('only approved franchise holders can open the member products page', function () {
    $this->get(route('member.products'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->pending()->create())->get(route('member.products'))->assertRedirect(route('account.pending'));
});

test('the dashboard products also have offer prices and add to cart', function () {
    Product::factory()->create(['name' => 'Herbal Hair Oil', 'price' => 95, 'mrp' => 130, 'stock' => 20]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Available products and offer prices')
        ->assertSee('MRP ₹130.00')
        ->assertSee('Add to cart');
});

test('admin sets the offer price and mrp from the product form', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.products.create')
        ->set('name', 'Offer Oil')
        ->set('price', '95')
        ->set('mrp', '130')
        ->set('stock', '10')
        ->assertSee('Offer price (₹)')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('slug', 'offer-oil')->firstOrFail();

    expect((float) $product->price)->toBe(95.0)->and((float) $product->mrp)->toBe(130.0);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.edit', ['product' => $product])
        ->assertSet('mrp', '130.00')
        ->set('mrp', '-5')
        ->call('save')
        ->assertHasErrors(['mrp']);
});

// ---- Sales manager details -------------------------------------------------

test('the dashboard shows the sales managers name, contact number and email', function () {
    $manager = User::factory()->salesManager()->create(['name' => 'Rohit Kulkarni', 'email' => 'rohit@pradiksha.test', 'phone' => '9876501234']);

    $this->actingAs(User::factory()->create(['sales_manager_id' => $manager->id]))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My sales manager')
        ->assertSee('Rohit Kulkarni')
        ->assertSee('Contact number')
        ->assertSee('9876501234')
        ->assertSee('tel:9876501234', false)
        ->assertSee('Email ID')
        ->assertSee('mailto:rohit@pradiksha.test', false);
});

test('a missing sales manager phone number is shown as not provided', function () {
    $manager = User::factory()->salesManager()->create(['phone' => null]);

    $this->actingAs(User::factory()->create(['sales_manager_id' => $manager->id]))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Not provided yet');
});

test('the admin forms require a valid contact number for a sales manager', function (string $phone) {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.create')
        ->set('name', 'New Manager')
        ->set('email', 'manager@example.com')
        ->set('phone', $phone)
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertHasErrors(['phone']);

    expect(User::where('email', 'manager@example.com')->exists())->toBeFalse();
})->with(['blank' => '', 'letters' => 'call me', 'too short' => '123']);

test('admin can change a sales managers contact number', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create(['phone' => '9000000000']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.edit', ['salesManager' => $manager])
        ->assertSet('phone', '9000000000')
        ->set('phone', '+91 98765 43210')
        ->call('save')
        ->assertHasNoErrors();

    expect($manager->refresh()->phone)->toBe('+91 98765 43210');
});

// ---- Shipping: the admin saves AWB details --------------------------------

function paidShippableOrder(array $attributes = []): Order
{
    return Order::factory()->paid()->create($attributes);
}

test('admin saves the courier, AWB number and tracking link, which marks the order shipped', function () {
    $admin = User::factory()->admin()->create();
    $order = paidShippableOrder(['status' => OrderStatus::Confirmed]);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('courierName', 'Delhivery')
        ->set('awbNumber', 'DL1234567890')
        ->set('trackingUrl', 'https://www.delhivery.com/track/package/DL1234567890')
        ->call('saveShipping')
        ->assertHasNoErrors()
        ->assertSet('status', 'shipped');

    $order->refresh();

    expect($order->courier_name)->toBe('Delhivery')
        ->and($order->awb_number)->toBe('DL1234567890')
        ->and($order->tracking_url)->toBe('https://www.delhivery.com/track/package/DL1234567890')
        ->and($order->dispatched_at)->not->toBeNull()
        ->and($order->status)->toBe(OrderStatus::Shipped)
        ->and($order->isDispatched())->toBeTrue();
});

test('the courier and tracking link are optional but the AWB number is required', function () {
    $admin = User::factory()->admin()->create();
    $order = paidShippableOrder(['status' => OrderStatus::Processing]);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->call('saveShipping')
        ->assertHasErrors(['awbNumber' => 'required']);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('awbNumber', 'AWB-998877')
        ->call('saveShipping')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->courier_name)->toBeNull()->and($order->tracking_url)->toBeNull()->and($order->awb_number)->toBe('AWB-998877');
});

test('only web links are accepted as a tracking link', function (string $url) {
    $admin = User::factory()->admin()->create();
    $order = paidShippableOrder();

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('awbNumber', 'AWB123')
        ->set('trackingUrl', $url)
        ->call('saveShipping')
        ->assertHasErrors(['trackingUrl']);

    expect($order->refresh()->awb_number)->toBeNull();
})->with(['javascript' => 'javascript:alert(1)', 'not a link' => 'track it online', 'ftp' => 'ftp://example.com/track']);

test('the AWB number cannot contain odd characters', function () {
    $admin = User::factory()->admin()->create();
    $order = paidShippableOrder();

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('awbNumber', '<script>alert(1)</script>')
        ->call('saveShipping')
        ->assertHasErrors(['awbNumber']);
});

test('shipping details cannot be saved for an unpaid order', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('awbNumber', 'AWB123')
        ->call('saveShipping');

    expect($order->refresh()->awb_number)->toBeNull();
});

test('saving shipping details does not move a delivered order back to shipped, and keeps the first dispatch time', function () {
    $admin = User::factory()->admin()->create();
    $order = paidShippableOrder(['status' => OrderStatus::Delivered, 'awb_number' => 'OLD1', 'dispatched_at' => now()->subDays(3)]);
    $firstDispatch = $order->dispatched_at->toDateTimeString();

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('awbNumber', 'NEW2')
        ->call('saveShipping')
        ->assertHasNoErrors();

    $order->refresh();

    expect($order->awb_number)->toBe('NEW2')
        ->and($order->status)->toBe(OrderStatus::Delivered)
        ->and($order->dispatched_at->toDateTimeString())->toBe($firstDispatch);
});

test('the admin order page only offers shipping details for paid orders', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.orders.show', paidShippableOrder()))->assertOk()->assertSee('Shipping details')->assertSee('AWB number');
    $this->actingAs($admin)->get(route('admin.orders.show', Order::factory()->create()))->assertOk()->assertDontSee('Save shipping details');
});

// ---- Shipping: what the franchise holder sees -----------------------------

test('a dispatched order shows the courier, AWB number and a track button that opens safely in a new tab', function () {
    $user = User::factory()->create();
    $order = paidShippableOrder([
        'user_id' => $user->id,
        'status' => OrderStatus::Shipped,
        'courier_name' => 'Delhivery',
        'awb_number' => 'DL1234567890',
        'tracking_url' => 'https://www.delhivery.com/track/package/DL1234567890',
        'dispatched_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Shipment tracking')
        ->assertSee('Delhivery')
        ->assertSee('DL1234567890')
        ->assertSee('Track shipment')
        ->assertSee('href="https://www.delhivery.com/track/package/DL1234567890"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false);
});

test('an AWB number without a tracking link still shows the number but no track button', function () {
    $user = User::factory()->create();
    $order = paidShippableOrder(['user_id' => $user->id, 'status' => OrderStatus::Shipped, 'awb_number' => 'AWB777', 'dispatched_at' => now()]);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('AWB777')
        ->assertDontSee('Track shipment');
});

test('a paid order that has not been dispatched says so', function () {
    $user = User::factory()->create();
    $order = paidShippableOrder(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Not dispatched yet')
        ->assertSee('Delivery address');
});

test('an unpaid order shows no shipping section', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('orders.show', $order))->assertOk()->assertDontSee('Shipment tracking');
});

test('orders in progress on the dashboard show the AWB number and a track link', function () {
    $user = User::factory()->create();
    paidShippableOrder([
        'user_id' => $user->id,
        'status' => OrderStatus::Shipped,
        'courier_name' => 'DTDC',
        'awb_number' => 'D9988776',
        'tracking_url' => 'https://www.dtdc.in/tracking.asp?awb=D9988776',
        'dispatched_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('DTDC')
        ->assertSee('AWB D9988776')
        ->assertSee('href="https://www.dtdc.in/tracking.asp?awb=D9988776"', false);
});

test('a sales manager sees the shipping details of their franchises orders', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['sales_manager_id' => $manager->id]);
    $order = paidShippableOrder(['user_id' => $holder->id, 'status' => OrderStatus::Shipped, 'awb_number' => 'SM555', 'dispatched_at' => now()]);

    $this->actingAs($manager)->get(route('sales.orders.show', $order))->assertOk()->assertSee('SM555');
});

// ---- The packages page layout ----------------------------------------------

test('the package products open in one panel below the cards, four across on desktop and two on mobile', function () {
    Storage::fake('public');

    foreach ([
        'pradiksha-ortho-oil-100ml-joint-relief-oil', 'pradiksha-onion-shampoo-200ml', 'pradiksha-besto-slim-powder',
        'mushroom-ad-soup-powder-for-weight-gain', 'pradiksha-diafit-powder', 'shila-shakti-capsule',
    ] as $slug) {
        Product::factory()->create(['slug' => $slug, 'stock' => 100]);
    }

    (new FranchisePackagesSeeder)->run();

    $html = $this->get(route('packages.index'))->assertOk()->getContent();

    // Mobile shows two products per row, from the medium breakpoint upwards four.
    expect($html)->toContain('grid grid-cols-2 gap-4 md:grid-cols-4" data-package-products="basic"')
        ->toContain('data-package-products="mini"')
        ->toContain('data-package-products="super"');

    // The product panels come after all three package cards, not inside them.
    $lastCard = strrpos($html, 'data-package="super"');

    expect(strpos($html, 'data-package-products="basic"'))->toBeGreaterThan($lastCard);

    // One shared state decides which package is open, and the button toggles it.
    expect($html)->toContain('open: null')->toContain('toggle(')->toContain('Explore Package');

    expect(Package::count())->toBe(3);
});
