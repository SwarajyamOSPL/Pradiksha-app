<?php

use App\Actions\ApproveFranchiseHolder;
use App\Enums\OrderStatus;
use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use App\Notifications\FranchiseApproved;
use Database\Seeders\DummyProductPriceSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('after signing up the new franchise holder is told the request went to the admin', function () {
    $this->post(route('register.store'), [
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
    ])->assertRedirect(route('login'));

    $this->assertGuest();

    $this->get(route('login'))->assertSee('Your request has been sent to our admin for approval');
});

test('a pending franchise holder cannot log in and is told why', function () {
    $user = User::factory()->pending()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Your registration is awaiting admin approval. You will be able to log in once it is approved.']);

    $this->assertGuest();
});

test('a suspended account cannot log in', function () {
    $user = User::factory()->create(['status' => UserStatus::Suspended]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a wrong password never reveals that an account is pending', function () {
    $user = User::factory()->pending()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->not->toContain('awaiting admin approval');
});

test('once approved the franchise holder can log in and reaches their dashboard', function () {
    $user = User::factory()->pending()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    app(ApproveFranchiseHolder::class)($user);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('the admin dashboard lists pending requests and can approve one, which emails the franchise holder', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $pending = User::factory()->pending()->create(['business_name' => 'Green Leaf Franchise']);

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->assertSee('Green Leaf Franchise')
        ->call('approveHolder', $pending->id)
        ->assertDontSee('Green Leaf Franchise');

    expect($pending->refresh()->status)->toBe(UserStatus::Approved);

    Notification::assertSentTo($pending, FranchiseApproved::class);
});

test('approving from the franchise holders pages also emails them, and only once', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $pending = User::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.franchise-holders.index')
        ->call('updateStatus', $pending->id, 'approved')
        ->call('updateStatus', $pending->id, 'approved');

    Livewire::actingAs($admin)
        ->test('pages::admin.franchise-holders.show', ['user' => $pending])
        ->call('updateStatus', 'approved');

    Notification::assertSentToTimes($pending, FranchiseApproved::class, 1);
});

test('suspending a franchise holder does not send the approval email', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.franchise-holders.index')
        ->call('updateStatus', $holder->id, 'suspended');

    Notification::assertNothingSent();
    expect($holder->refresh()->status)->toBe(UserStatus::Suspended);
});

test('only franchise holders can be approved from the dashboard', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    $component = Livewire::actingAs($admin)->test('pages::admin.dashboard');

    expect(fn () => $component->call('approveHolder', $manager->id))
        ->toThrow(ModelNotFoundException::class);

    expect($manager->refresh()->status)->toBe(UserStatus::Approved);
});

test('the dummy price seeder fills only products with no price and keeps real prices', function () {
    $unpriced = Product::factory()->create(['name' => 'Cough Syrup 100ml', 'price' => null]);
    $jar = Product::factory()->create(['name' => 'Onion Hair Oil ( JAR 5 LTR)', 'price' => null]);
    $real = Product::factory()->create(['name' => 'Herbal Powder', 'price' => 777.77]);

    (new DummyProductPriceSeeder)->run();

    expect((float) $unpriced->refresh()->price)->toBe(215.0)
        ->and((float) $jar->refresh()->price)->toBe(3250.0)
        ->and((float) $real->refresh()->price)->toBe(777.77);
});

test('seeded prices flow through to packages that contain those products', function () {
    $product = Product::factory()->create(['name' => 'Herbal Powder', 'price' => null]);
    $package = Package::factory()->create();
    $package->products()->attach($product, ['quantity' => 2]);

    (new DummyProductPriceSeeder)->run();

    expect((float) $package->refresh()->subtotal)->toBe(1130.0)
        ->and((float) $package->price)->toBe(1130.0);
});

test('the franchise dashboard shows products with their prices', function () {
    $user = User::factory()->create();
    Product::factory()->create(['name' => 'Herbal Hair Oil', 'price' => 285]);
    Product::factory()->create(['name' => 'Hidden Product', 'price' => 100, 'is_active' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Products and franchise prices')
        ->assertSee('Herbal Hair Oil')
        ->assertSee('₹285.00')
        ->assertDontSee('Hidden Product');
});

test('the franchise dashboard shows the profile and the assigned sales manager', function () {
    $manager = User::factory()->salesManager()->create(['name' => 'Rohit Kulkarni', 'email' => 'rohit@pradiksha.test']);
    $user = User::factory()->create([
        'name' => 'Asha Verma',
        'business_name' => 'Green Leaf Franchise',
        'phone' => '9876543210',
        'city' => 'Pune',
        'sales_manager_id' => $manager->id,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My profile')
        ->assertSee('Green Leaf Franchise')
        ->assertSee('9876543210')
        ->assertSee('Pune')
        ->assertSee('My sales manager')
        ->assertSee('Rohit Kulkarni')
        ->assertSee('rohit@pradiksha.test');
});

test('the franchise dashboard explains when no sales manager is assigned yet', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('A sales manager has not been assigned to you yet');
});

test('the franchise dashboard shows paid orders in progress with where they are', function () {
    $user = User::factory()->create();
    $shipped = Order::factory()->paid()->create(['user_id' => $user->id, 'status' => OrderStatus::Shipped]);
    $delivered = Order::factory()->paid()->create(['user_id' => $user->id, 'status' => OrderStatus::Delivered]);
    $unpaid = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Orders in progress')
        ->assertSee('In transit, on its way to you')
        ->assertSee(route('orders.show', $shipped))
        ->assertSee('Order history');

    // Delivered and unpaid orders are not "in progress".
    $html = $this->actingAs($user)->get(route('dashboard'))->getContent();
    $inProgress = str($html)->between('Orders in progress', 'Products and franchise prices')->toString();

    expect($inProgress)->not->toContain(route('orders.show', $delivered))
        ->and($inProgress)->not->toContain(route('orders.show', $unpaid));
});

test('there is no in progress section when nothing is on its way', function () {
    $user = User::factory()->create();
    Order::factory()->paid()->create(['user_id' => $user->id, 'status' => OrderStatus::Delivered]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Orders in progress');
});

test('the order page tracks a shipped order through its stages', function () {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id, 'status' => OrderStatus::Shipped]);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Order status')
        ->assertSee('In transit, on its way to you')
        ->assertSeeInOrder(['Confirmed', 'Packing', 'In transit', 'Delivered'])
        ->assertSee('aria-current="step"', false);
});

test('the order page shows a delivered order as complete', function () {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id, 'status' => OrderStatus::Delivered]);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Delivered')
        ->assertDontSee('aria-current="step"', false);
});

test('the order page shows a cancelled order clearly', function () {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $user->id, 'status' => OrderStatus::Cancelled]);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('This order was cancelled')
        ->assertDontSee('Order progress');
});

test('an unpaid order shows no delivery tracker', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertDontSee('Order progress');
});
