<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;

test('non-admins cannot access the admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
});

test('app layout keeps the main content outside the sidebar', function () {
    $admin = User::factory()->admin()->create();

    $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

    $document = new DOMDocument;
    @$document->loadHTML($html, LIBXML_NOERROR);
    $xpath = new DOMXPath($document);

    expect($xpath->query('//*[@data-flux-sidebar]'))->toHaveCount(1)
        ->and($xpath->query('//*[@data-flux-main]'))->toHaveCount(1)
        ->and($xpath->query('//*[@data-flux-sidebar]//*[@data-flux-main]'))->toHaveCount(0);
});

test('admin dashboard renders for an empty system', function () {
    $admin = User::factory()->admin()->create(['name' => 'Asha Verma']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Welcome back, Asha')
        ->assertSee('No orders yet')
        ->assertSee('All caught up');
});

test('admin dashboard totals exclude cancelled orders from order value', function () {
    $admin = User::factory()->admin()->create();
    Order::factory()->create(['status' => OrderStatus::Delivered, 'total_amount' => 1000]);
    Order::factory()->create(['status' => OrderStatus::Pending, 'total_amount' => 500]);
    Order::factory()->create(['status' => OrderStatus::Cancelled, 'total_amount' => 9999]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('₹1,500.00')
        ->assertDontSee('₹11,499.00')
        ->assertSee('1 awaiting action');
});

test('admin dashboard lists recent orders and links to them', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => OrderStatus::Shipped]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('#'.$order->id)
        ->assertSee('Shipped')
        ->assertSee(route('admin.orders.show', $order));
});

test('admin dashboard surfaces franchise holders awaiting approval', function () {
    $admin = User::factory()->admin()->create();
    $pending = User::factory()->pending()->create(['business_name' => 'Green Leaf Traders', 'city' => 'Pune']);
    User::factory()->create(['business_name' => 'Already Approved Co']);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('1 franchise holder is waiting for approval')
        ->assertSee('Green Leaf Traders')
        ->assertSee(route('admin.franchise-holders.show', $pending))
        ->assertDontSee('Already Approved Co')
        ->assertDontSee('All caught up');
});

test('admin dashboard counts only active products and all packages', function () {
    $admin = User::factory()->admin()->create();
    Product::factory()->count(2)->create(['is_active' => true]);
    Product::factory()->create(['is_active' => false]);
    Package::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Active products', '2', '3 packages']);
});
