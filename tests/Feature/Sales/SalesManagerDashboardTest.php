<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('non-sales-managers cannot access the sales dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('sales.dashboard'))->assertForbidden();
});

test('sales managers are redirected from the generic dashboard to their own', function () {
    $manager = User::factory()->salesManager()->create();

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertRedirect(route('sales.dashboard'));
});

test('the dashboard shows the order history of all assigned franchises together', function () {
    $manager = User::factory()->salesManager()->create();
    $pune = User::factory()->create(['business_name' => 'Pune Wellness', 'city' => 'Pune', 'sales_manager_id' => $manager->id]);
    $nagpur = User::factory()->create(['business_name' => 'Nagpur Herbals', 'city' => 'Nagpur', 'sales_manager_id' => $manager->id]);

    $puneOrder = Order::factory()->paid()->create(['user_id' => $pune->id]);
    $nagpurOrder = Order::factory()->paid()->create(['user_id' => $nagpur->id]);

    $this->actingAs($manager)
        ->get(route('sales.dashboard'))
        ->assertOk()
        ->assertSee(route('sales.orders.show', $puneOrder))
        ->assertSee(route('sales.orders.show', $nagpurOrder))
        ->assertSee('Pune Wellness')
        ->assertSee('Nagpur Herbals')
        ->assertSee(route('sales.orders.show', $puneOrder));
});

test('the dashboard never shows orders of franchises assigned to someone else or to nobody', function () {
    $manager = User::factory()->salesManager()->create();
    $otherManager = User::factory()->salesManager()->create();

    $mine = User::factory()->create(['sales_manager_id' => $manager->id]);
    $theirs = User::factory()->create(['business_name' => 'Someone Elses Shop', 'sales_manager_id' => $otherManager->id]);
    $unassigned = User::factory()->create(['business_name' => 'Unassigned Shop']);

    $myOrder = Order::factory()->paid()->create(['user_id' => $mine->id]);
    $theirOrder = Order::factory()->paid()->create(['user_id' => $theirs->id]);
    $unassignedOrder = Order::factory()->paid()->create(['user_id' => $unassigned->id]);

    $this->actingAs($manager)
        ->get(route('sales.dashboard'))
        ->assertOk()
        ->assertSee(route('sales.orders.show', $myOrder))
        ->assertDontSee(route('sales.orders.show', $theirOrder))
        ->assertDontSee(route('sales.orders.show', $unassignedOrder))
        ->assertDontSee('Someone Elses Shop');
});

test('the dashboard only lists paid orders, not abandoned payment attempts', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['sales_manager_id' => $manager->id]);

    $paid = Order::factory()->paid()->create(['user_id' => $holder->id]);
    $unpaid = Order::factory()->create(['user_id' => $holder->id]);

    $this->actingAs($manager)
        ->get(route('sales.dashboard'))
        ->assertOk()
        ->assertSee(route('sales.orders.show', $paid))
        ->assertDontSee(route('sales.orders.show', $unpaid));
});

test('the dashboard totals cover assigned paid orders and exclude cancelled value', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['sales_manager_id' => $manager->id]);
    $stranger = User::factory()->create();

    Order::factory()->paid()->create(['user_id' => $holder->id, 'status' => OrderStatus::Delivered, 'total_amount' => 1000]);
    Order::factory()->paid()->create(['user_id' => $holder->id, 'status' => OrderStatus::Confirmed, 'total_amount' => 500]);
    Order::factory()->paid()->create(['user_id' => $holder->id, 'status' => OrderStatus::Cancelled, 'total_amount' => 9999]);
    Order::factory()->paid()->create(['user_id' => $stranger->id, 'total_amount' => 7777]);

    $this->actingAs($manager)
        ->get(route('sales.dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Total orders', '3'])
        ->assertSee('₹1,500.00')
        ->assertDontSee('7,777');
});

test('the order history can be filtered by franchise and by status', function () {
    $manager = User::factory()->salesManager()->create();
    $first = User::factory()->create(['sales_manager_id' => $manager->id]);
    $second = User::factory()->create(['sales_manager_id' => $manager->id]);

    $firstOrder = Order::factory()->paid()->create(['user_id' => $first->id, 'status' => OrderStatus::Shipped]);
    $secondOrder = Order::factory()->paid()->create(['user_id' => $second->id, 'status' => OrderStatus::Delivered]);

    Livewire::actingAs($manager)
        ->test('pages::sales.dashboard')
        ->set('franchise', (string) $first->id)
        ->assertSee(route('sales.orders.show', $firstOrder))
        ->assertDontSee(route('sales.orders.show', $secondOrder))
        ->set('franchise', '')
        ->set('status', OrderStatus::Delivered->value)
        ->assertSee(route('sales.orders.show', $secondOrder))
        ->assertDontSee(route('sales.orders.show', $firstOrder));
});

test('the dashboard tells a new sales manager they have no franchises yet', function () {
    $manager = User::factory()->salesManager()->create();

    $this->actingAs($manager)
        ->get(route('sales.dashboard'))
        ->assertOk()
        ->assertSee("You don't have any franchises assigned yet.");
});

test('a sales manager can open the details of an assigned franchises order', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['business_name' => 'Pune Wellness', 'sales_manager_id' => $manager->id]);
    $order = Order::factory()->paid()->create(['user_id' => $holder->id]);
    $order->items()->create(['product_id' => Product::factory()->create()->id, 'product_name' => 'Herbal Hair Oil', 'unit_price' => 100, 'quantity' => 2, 'line_total' => 200]);

    $this->actingAs($manager)
        ->get(route('sales.orders.show', $order))
        ->assertOk()
        ->assertSee('Order #'.$order->id)
        ->assertSee('Pune Wellness')
        ->assertSee('Herbal Hair Oil');
});

test('a sales manager cannot open an order from a franchise that is not theirs', function () {
    $manager = User::factory()->salesManager()->create();
    $stranger = User::factory()->create(['sales_manager_id' => User::factory()->salesManager()->create()->id]);
    $order = Order::factory()->paid()->create(['user_id' => $stranger->id]);

    $this->actingAs($manager)
        ->get(route('sales.orders.show', $order))
        ->assertForbidden();
});

test('a franchise holder cannot open the sales order page', function () {
    $holder = User::factory()->create();
    $order = Order::factory()->paid()->create(['user_id' => $holder->id]);

    $this->actingAs($holder)->get(route('sales.orders.show', $order))->assertForbidden();
});

test('a sales manager only sees their assigned franchises in my franchises', function () {
    $manager = User::factory()->salesManager()->create();
    $otherManager = User::factory()->salesManager()->create();

    User::factory()->create(['name' => 'Assigned Holder', 'city' => 'Pune', 'sales_manager_id' => $manager->id]);
    User::factory()->create(['name' => 'Unassigned Holder', 'sales_manager_id' => $otherManager->id]);

    $this->actingAs($manager)
        ->get(route('sales.franchise-holders.index'))
        ->assertOk()
        ->assertSee('Assigned Holder')
        ->assertSee('Pune')
        ->assertDontSee('Unassigned Holder');
});

test('a sales manager can view an assigned franchise holders paid order history', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['sales_manager_id' => $manager->id]);
    $order = Order::factory()->paid()->create(['user_id' => $holder->id]);
    $unpaid = Order::factory()->create(['user_id' => $holder->id]);

    $this->actingAs($manager)
        ->get(route('sales.franchise-holders.show', $holder))
        ->assertOk()
        ->assertSee(route('sales.orders.show', $order))
        ->assertDontSee(route('sales.orders.show', $unpaid));
});

test('a sales manager cannot view a franchise holder not assigned to them', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create();

    $this->actingAs($manager)
        ->get(route('sales.franchise-holders.show', $holder))
        ->assertForbidden();
});
