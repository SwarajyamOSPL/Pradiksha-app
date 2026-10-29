<?php

use App\Actions\MarkOrderAsPaid;
use App\Enums\UserStatus;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use App\Notifications\FranchiseApproved;
use Database\Seeders\FranchisePackagesSeeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');

    config([
        'services.razorpay.key_id' => 'rzp_test_key',
        'services.razorpay.key_secret' => 'test_secret',
        'invoice.tax.rate' => 0,
        'franchise.auto_approve_on_package_payment' => true,
    ]);

    Http::fake(['api.razorpay.com/v1/orders' => Http::response(['id' => 'order_TEST123'])]);
});

/**
 * The catalogue products the packages use that already exist on the real site.
 */
function createExistingCatalogue(): void
{
    foreach ([
        'pradiksha-ortho-oil-100ml-joint-relief-oil' => 'Pradiksha Ortho Oil 100ml - Joint Relief Oil',
        'pradiksha-onion-shampoo-200ml' => 'Pradiksha ONION SHAMPOO 200ML',
        'pradiksha-besto-slim-powder' => 'Pradiksha Besto Slim powder',
        'mushroom-ad-soup-powder-for-weight-gain' => 'Mushroom AD Soup Powder For weight Gain',
        'pradiksha-diafit-powder' => 'Pradiksha Diafit Powder',
        'shila-shakti-capsule' => 'Shila Shakti Capsule',
    ] as $slug => $name) {
        Product::factory()->create(['slug' => $slug, 'name' => $name, 'stock' => 100, 'price' => 200]);
    }
}

function seedPackages(): void
{
    createExistingCatalogue();

    (new FranchisePackagesSeeder)->run();
}

function packageNamed(string $slug): Package
{
    return Package::query()->with('products')->where('slug', $slug)->firstOrFail();
}

function signupPaymentSignature(string $razorpayOrderId = 'order_TEST123'): array
{
    return [
        'razorpay_order_id' => $razorpayOrderId,
        'razorpay_payment_id' => 'pay_ABC',
        'razorpay_signature' => hash_hmac('sha256', $razorpayOrderId.'|pay_ABC', 'test_secret'),
    ];
}

// ---- The packages and the seeder ------------------------------------------

test('the seeder creates Basic, Mini and Super with fixed prices and twelve products each', function () {
    seedPackages();

    $packages = Package::query()->with('products')->orderBy('price')->get();

    expect($packages->pluck('name')->all())->toBe(['Basic', 'Mini', 'Super'])
        ->and($packages->map(fn (Package $package) => (float) $package->price)->all())->toBe([5000.0, 10000.0, 25000.0])
        ->and($packages->every(fn (Package $package) => $package->hasFixedPrice() && $package->is_active && $package->products->count() === 12))->toBeTrue()
        ->and($packages->map(fn (Package $package) => $package->unitCount())->all())->toBe([46, 95, 230]);
});

test('each package has the quantities from its sheet', function () {
    seedPackages();

    $quantity = fn (string $package, string $product) => packageNamed($package)->products->firstWhere('slug', $product)->pivot->quantity;

    expect($quantity('basic', 'sar-balm'))->toBe(11)
        ->and($quantity('basic', 'pradiksha-ortho-oil-50ml'))->toBe(5)
        ->and($quantity('mini', 'pradiksha-ortho-syrup-200ml'))->toBe(15)
        ->and($quantity('mini', 'sar-balm'))->toBe(8)
        ->and($quantity('super', 'pradiksha-ortho-oil-100ml-joint-relief-oil'))->toBe(48)
        ->and($quantity('super', 'shila-shakti-capsule'))->toBe(13)
        ->and($quantity('super', 'pradiksha-piles-care-capsules-30-caps'))->toBe(5);
});

test('the seeder adds the missing products with their photos and sheet prices', function () {
    seedPackages();

    $oil = Product::where('slug', 'pradiksha-ortho-oil-50ml')->firstOrFail();
    $syrup = Product::where('slug', 'pradiksha-ortho-syrup-200ml')->firstOrFail();

    expect((float) $oil->price)->toBe(130.0)
        ->and($oil->stock)->toBe(100)
        ->and($oil->image_path)->toBe('products/ortho-oil-50ml.webp')
        ->and((float) $syrup->price)->toBe(250.0)
        ->and($syrup->image_path)->toBeNull();

    foreach (['ortho-oil-50ml', 'ortho-capsules-60', 'onion-hair-oil-200ml', 'piles-care-capsules-30', 'sar-balm'] as $photo) {
        Storage::disk('public')->assertExists("products/{$photo}.webp");
    }
});

test('a fixed price package ignores its products prices', function () {
    seedPackages();

    Product::where('slug', 'sar-balm')->firstOrFail()->update(['price' => 9999]);

    expect((float) packageNamed('basic')->price)->toBe(5000.0);
});

test('running the seeder again does not duplicate anything or overwrite admin edits', function () {
    seedPackages();

    Package::where('slug', 'basic')->update(['fixed_price' => 5500, 'price' => 5500, 'name' => 'Basic Plus']);
    $productCount = Product::count();

    (new FranchisePackagesSeeder)->run();

    expect(Package::count())->toBe(3)
        ->and(Product::count())->toBe($productCount)
        ->and((float) packageNamed('basic')->price)->toBe(5500.0)
        ->and(packageNamed('basic')->name)->toBe('Basic Plus')
        ->and(packageNamed('basic')->products)->toHaveCount(12);
});

test('the seeder refuses to build a package when a catalogue product is missing', function () {
    expect(fn () => (new FranchisePackagesSeeder)->run())
        ->toThrow(RuntimeException::class, 'not in the catalogue');

    expect(Package::count())->toBe(0);
});

// ---- The packages page -----------------------------------------------------

test('guests see the three packages with their prices and an explore button', function () {
    seedPackages();

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSeeInOrder(['Basic', '₹5,000', 'Mini', '₹10,000', 'Super', '₹25,000'])
        ->assertSee('Explore Package')
        ->assertSee('12 products')
        ->assertSee('46 units')
        ->assertSee('230 units');
});

test('every product and its photo is inside the package card ready to expand', function () {
    seedPackages();

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Pradiksha Ortho Oil 50ml')
        ->assertSee('Pradiksha Piles Care Capsules 30 caps')
        ->assertSee('SAR Balm')
        ->assertSee('products/ortho-oil-50ml.webp', false)
        ->assertSee('products/sar-balm.webp', false)
        ->assertSee('&times;48', false);
});

test('the select button sends a guest to register with that package', function () {
    seedPackages();

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee(route('register', ['package' => 'basic']), false)
        ->assertSee(route('register', ['package' => 'mini']), false)
        ->assertSee(route('register', ['package' => 'super']), false);
});

test('a franchise holder selecting a package goes to its payment page', function () {
    seedPackages();

    $this->actingAs(User::factory()->create())
        ->get(route('packages.index'))
        ->assertOk()
        ->assertSee(route('packages.checkout', packageNamed('super')), false)
        ->assertDontSee(route('register', ['package' => 'super']), false);
});

test('admins and sales managers browsing the packages cannot select one', function () {
    seedPackages();

    foreach ([User::factory()->admin()->create(), User::factory()->salesManager()->create()] as $user) {
        $this->actingAs($user)
            ->get(route('packages.index'))
            ->assertOk()
            ->assertSee('Explore Package')
            ->assertDontSee(route('packages.checkout', packageNamed('basic')), false)
            ->assertDontSee(route('register', ['package' => 'basic']), false);
    }
});

test('a package with an out of stock product cannot be selected', function () {
    seedPackages();

    Product::where('slug', 'pradiksha-ortho-oil-100ml-joint-relief-oil')->update(['stock' => 3]);

    // Mini and Super need more than 3, Basic needs 5: all three are short.
    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Out of stock')
        ->assertDontSee(route('register', ['package' => 'basic']), false);
});

test('only the package that is short is blocked', function () {
    seedPackages();

    Product::where('slug', 'pradiksha-ortho-oil-100ml-joint-relief-oil')->update(['stock' => 30]);

    // Basic (5) and Mini (24) can still be supplied, Super (48) cannot.
    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee(route('register', ['package' => 'basic']), false)
        ->assertSee(route('register', ['package' => 'mini']), false)
        ->assertDontSee(route('register', ['package' => 'super']), false);
});

test('the single package page shows the public price and the select button', function () {
    seedPackages();

    $this->get(route('packages.show', packageNamed('mini')))
        ->assertOk()
        ->assertSee('₹10,000.00')
        ->assertSee('Select this package')
        ->assertSee(route('register', ['package' => 'mini']), false);
});

// ---- Registering through a package ----------------------------------------

test('the register page says which package is being bought', function () {
    seedPackages();

    $this->get(route('register', ['package' => 'basic']))
        ->assertOk()
        ->assertSee('Register to buy the Basic package')
        ->assertSee('₹5,000.00')
        ->assertSee('12 products')
        ->assertSee('name="package" value="basic"', false);
});

test('an unknown or inactive package is ignored on the register page', function () {
    seedPackages();
    Package::where('slug', 'mini')->update(['is_active' => false]);

    $this->get(route('register', ['package' => 'nope']))->assertOk()->assertDontSee('Register to buy')->assertDontSee('name="package"', false);
    $this->get(route('register', ['package' => 'mini']))->assertOk()->assertDontSee('Register to buy');
});

test('the register page explains when the chosen package is out of stock', function () {
    seedPackages();
    Product::where('slug', 'sar-balm')->update(['stock' => 0]);

    $this->get(route('register', ['package' => 'basic']))
        ->assertOk()
        ->assertSee('The Basic package is out of stock right now')
        ->assertDontSee('name="package"', false);
});

function registrationDetails(array $overrides = []): array
{
    return [
        'name' => 'Asha Verma',
        'email' => 'asha@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'phone' => '9876543210',
        'business_name' => 'Green Leaf Franchise',
        'address' => '14 MG Road',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'pincode' => '411001',
        ...$overrides,
    ];
}

test('registering through a package logs the user in and sends them straight to payment', function () {
    seedPackages();
    $basic = packageNamed('basic');

    $this->post(route('register.store'), registrationDetails(['package' => 'basic']))
        ->assertRedirect(route('packages.checkout', $basic))
        ->assertSessionHas('status');

    $user = User::where('email', 'asha@example.com')->firstOrFail();

    expect($user->signup_package_id)->toBe($basic->id)
        ->and($user->status)->toBe(UserStatus::Pending);

    $this->assertAuthenticatedAs($user);
});

test('registering without a package still waits for admin approval and is not logged in', function () {
    seedPackages();

    $this->post(route('register.store'), registrationDetails())->assertRedirect(route('login'));

    expect(User::where('email', 'asha@example.com')->firstOrFail()->signup_package_id)->toBeNull();

    $this->assertGuest();
});

test('an invalid package in the signup is ignored', function () {
    seedPackages();

    $this->post(route('register.store'), registrationDetails(['package' => 'nope']))->assertRedirect(route('login'));

    expect(User::where('email', 'asha@example.com')->firstOrFail()->signup_package_id)->toBeNull();

    $this->assertGuest();
});

// ---- A signed up user before they have paid -------------------------------

test('a user who signed up for a package can open that packages payment page', function () {
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    $this->actingAs($user)->get(route('packages.checkout', $basic))->assertOk()->assertSee('Checkout')->assertSee('₹5,000.00');
});

test('they cannot reach other packages or the members only areas before paying', function () {
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    $this->actingAs($user)->get(route('packages.checkout', packageNamed('super')))->assertRedirect(route('packages.checkout', $basic));
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('packages.checkout', $basic));
    $this->actingAs($user)->get(route('cart.index'))->assertRedirect(route('packages.checkout', $basic));
    $this->actingAs($user)->get(route('orders.index'))->assertRedirect(route('packages.checkout', $basic));
});

test('someone pending with no package still cannot open any package payment page', function () {
    seedPackages();
    $user = User::factory()->pending()->create();

    $this->actingAs($user)->get(route('packages.checkout', packageNamed('basic')))->assertRedirect(route('account.pending'));
});

test('the pending page offers to continue to payment', function () {
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    $this->actingAs($user)
        ->get(route('account.pending'))
        ->assertOk()
        ->assertSee('Continue to payment')
        ->assertSee(route('packages.checkout', $basic), false);
});

test('someone who signed up for a package can log back in to finish paying', function () {
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);

    $this->get(route('dashboard'))->assertRedirect(route('packages.checkout', $basic));
});

// ---- Paying for the package ------------------------------------------------

test('paying for a fixed price package charges the package price and records the products as included', function () {
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $basic])
        ->call('pay')
        ->assertDispatched('start-razorpay');

    Http::assertSent(fn ($request) => $request['amount'] === 500000);

    $order = Order::where('user_id', $user->id)->with('items')->firstOrFail();

    expect((float) $order->total_amount)->toBe(5000.0)
        ->and((float) $order->subtotal)->toBe(5000.0)
        ->and($order->items)->toHaveCount(12)
        ->and($order->items->sum('quantity'))->toBe(46)
        ->and($order->itemsAreIncluded())->toBeTrue();
});

test('paying approves the new franchise holder automatically and takes the stock out', function () {
    Mail::fake();
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $basic])
        ->call('pay')
        ->call('confirmPayment', signupPaymentSignature());

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect($user->refresh()->status)->toBe(UserStatus::Approved)
        ->and($order->isPaid())->toBeTrue()
        ->and($order->invoice_number)->not->toBeNull()
        ->and(Product::where('slug', 'sar-balm')->first()->stock)->toBe(89)
        ->and(Product::where('slug', 'pradiksha-ortho-oil-100ml-joint-relief-oil')->first()->stock)->toBe(95);

    Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->hasTo($user->email));

    // Now approved, they can log in normally and see their dashboard.
    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

test('an auto approved member gets no separate approval email', function () {
    Mail::fake();
    Notification::fake();
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $basic])
        ->call('pay')
        ->call('confirmPayment', signupPaymentSignature());

    Notification::assertNotSentTo($user, FranchiseApproved::class);
});

test('with auto approval switched off a paying user stays pending but can see their order', function () {
    config(['franchise.auto_approve_on_package_payment' => false]);
    Mail::fake();
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $basic])
        ->call('pay')
        ->call('confirmPayment', signupPaymentSignature());

    $order = Order::where('user_id', $user->id)->firstOrFail();

    expect($user->refresh()->status)->toBe(UserStatus::Pending)
        ->and($user->hasPaidForSignupPackage())->toBeTrue()
        ->and($user->canPayForSignupPackage())->toBeFalse();

    $this->actingAs($user)->get(route('orders.show', $order))->assertOk();
    $this->actingAs($user)->get(route('orders.invoice', $order))->assertOk();
    $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('account.pending'));
    $this->actingAs($user)->get(route('account.pending'))->assertOk()->assertSee('Payment received');
});

test('with auto approval switched off they cannot log back in until an admin approves them', function () {
    config(['franchise.auto_approve_on_package_payment' => false]);
    Mail::fake();
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $basic])
        ->call('pay')
        ->call('confirmPayment', signupPaymentSignature());

    auth()->logout();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('buying a package never approves a suspended account', function () {
    Mail::fake();
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->create(['status' => UserStatus::Suspended, 'signup_package_id' => $basic->id]);
    $order = Order::factory()->create(['user_id' => $user->id, 'package_id' => $basic->id, 'type' => 'package', 'razorpay_order_id' => 'order_TEST123', 'total_amount' => 5000]);

    app(MarkOrderAsPaid::class)($order, 'pay_ABC');

    expect($user->refresh()->status)->toBe(UserStatus::Suspended);
});

test('the invoice and email show the package as one line with the products included', function () {
    Mail::fake();
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id, 'business_name' => 'Green Leaf Franchise']);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $basic])
        ->call('pay')
        ->call('confirmPayment', signupPaymentSignature());

    $order = Order::where('user_id', $user->id)->with('items', 'user', 'package')->firstOrFail();

    $html = view('invoices.invoice', [
        'order' => $order,
        'company' => config('invoice.company'),
        'tax' => config('invoice.tax'),
        'footerNote' => '',
    ])->render();

    expect($html)->toContain('Basic Package')->toContain('₹5,000.00')->toContain('Included')->toContain('SAR Balm');

    (new OrderConfirmationMail($order))
        ->assertSeeInHtml('Basic Package')
        ->assertSeeInHtml('₹5,000.00')
        ->assertSeeInHtml('Included');
});

test('the order page shows the included products instead of zero amounts', function () {
    Mail::fake();
    seedPackages();
    $basic = packageNamed('basic');
    $user = User::factory()->pending()->create(['signup_package_id' => $basic->id]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $basic])
        ->call('pay')
        ->call('confirmPayment', signupPaymentSignature());

    $order = Order::where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Included')
        ->assertSee('₹5,000.00')
        ->assertDontSee('₹0.00');
});

// ---- The admin package form ------------------------------------------------

test('admin can set a fixed price which replaces the calculated price and any discount', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('name', 'Special Kit')
        ->set('products', [['product_id' => (string) $product->id, 'quantity' => 3]])
        ->set('discount_type', 'percent')
        ->set('discount_value', '50')
        ->set('fixed_price', '5000')
        ->call('save')
        ->assertHasNoErrors();

    $package = Package::where('slug', 'special-kit')->firstOrFail();

    expect((float) $package->fixed_price)->toBe(5000.0)
        ->and((float) $package->price)->toBe(5000.0)
        ->and($package->discount_type)->toBeNull()
        ->and((float) $package->discount_value)->toBe(0.0);
});

test('the admin form previews the fixed price and hides the discount controls', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('products', [['product_id' => (string) $product->id, 'quantity' => 3]])
        ->assertSee('Products total')
        ->set('fixed_price', '5000')
        ->assertSee('₹5,000.00')
        ->assertDontSee('Products total')
        ->assertDontSee('No discount');
});

test('clearing the fixed price goes back to the calculated price', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => 100]);
    $package = Package::factory()->create(['fixed_price' => 5000, 'price' => 5000, 'subtotal' => 5000]);
    $package->products()->attach($product, ['quantity' => 2]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.edit', ['package' => $package])
        ->assertSet('fixed_price', '5000')
        ->set('fixed_price', '')
        ->call('save')
        ->assertHasNoErrors();

    $package->refresh();

    expect($package->fixed_price)->toBeNull()->and((float) $package->price)->toBe(200.0);
});

test('a negative or text fixed price is rejected', function (string $value) {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('name', 'Bad Kit')
        ->set('products', [['product_id' => (string) $product->id, 'quantity' => 1]])
        ->set('fixed_price', $value)
        ->call('save')
        ->assertHasErrors(['fixed_price']);

    expect(Package::where('slug', 'bad-kit')->exists())->toBeFalse();
})->with(['negative' => '-5', 'text' => 'abc']);
