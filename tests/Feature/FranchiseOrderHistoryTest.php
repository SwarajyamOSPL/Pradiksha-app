<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\FranchiseOrderHistory;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * @param  array<string, int>  $products  product name => quantity
 */
function historyOrder(User $holder, string $paidAt, array $products, float $total, OrderStatus $status = OrderStatus::Delivered): Order
{
    $order = Order::factory()->paid()->create([
        'user_id' => $holder->id,
        'status' => $status,
        'total_amount' => $total,
        'paid_at' => Carbon::parse($paidAt),
    ]);

    foreach ($products as $name => $quantity) {
        $order->items()->create([
            'product_id' => Product::factory()->create(['name' => $name])->id,
            'product_name' => $name,
            'unit_price' => 100,
            'quantity' => $quantity,
            'line_total' => 100 * $quantity,
        ]);
    }

    return $order;
}

test('the history adds up orders, units and amounts month by month, newest month first', function () {
    $holder = User::factory()->create();

    historyOrder($holder, '2026-08-05 10:00', ['Hair Oil' => 2], 200);
    historyOrder($holder, '2026-09-02 10:00', ['Hair Oil' => 1, 'Ortho Oil' => 4], 500);
    historyOrder($holder, '2026-09-20 10:00', ['Ortho Oil' => 1], 100);

    $months = (new FranchiseOrderHistory($holder))->months();

    expect($months->pluck('key')->all())->toBe(['2026-09', '2026-08'])
        ->and($months[0])->toMatchArray(['label' => 'September 2026', 'orders' => 2, 'units' => 6, 'amount' => 600.0])
        ->and($months[1])->toMatchArray(['label' => 'August 2026', 'orders' => 1, 'units' => 2, 'amount' => 200.0]);
});

test('cancelled and unpaid orders never count towards the totals', function () {
    $holder = User::factory()->create();

    historyOrder($holder, '2026-09-02 10:00', ['Hair Oil' => 1], 300);
    historyOrder($holder, '2026-09-03 10:00', ['Hair Oil' => 9], 9999, OrderStatus::Cancelled);
    Order::factory()->create(['user_id' => $holder->id, 'total_amount' => 7777]);

    $history = new FranchiseOrderHistory($holder);

    expect($history->summary())->toMatchArray(['orders' => 1, 'amount' => 300.0, 'units' => 1])
        ->and($history->months()->sum('amount'))->toBe(300.0)
        ->and((int) $history->products()->sum('units'))->toBe(1);
});

test('the history can be limited to one month and ignores a malformed month', function () {
    $holder = User::factory()->create();

    historyOrder($holder, '2026-08-05 10:00', ['Hair Oil' => 2], 200);
    historyOrder($holder, '2026-09-02 10:00', ['Ortho Oil' => 4], 500);

    $history = new FranchiseOrderHistory($holder);

    expect($history->summary('2026-08'))->toMatchArray(['orders' => 1, 'amount' => 200.0, 'units' => 2])
        ->and($history->orders('2026-09')->count())->toBe(1)
        ->and($history->products('2026-08')->pluck('name')->all())->toBe(['Hair Oil'])
        ->and($history->orders('not-a-month')->count())->toBe(2)
        ->and($history->orders('2026-13')->count())->toBe(2);
});

test('the history only covers the one franchise', function () {
    $holder = User::factory()->create();
    $other = User::factory()->create();

    historyOrder($holder, '2026-09-02 10:00', ['Hair Oil' => 1], 100);
    historyOrder($other, '2026-09-02 10:00', ['Ortho Oil' => 1], 8888);

    $history = new FranchiseOrderHistory($holder);

    expect($history->summary()['amount'])->toBe(100.0)
        ->and($history->products()->pluck('name')->all())->toBe(['Hair Oil']);
});

test('the products ordered are totalled across orders, most units first', function () {
    $holder = User::factory()->create();

    historyOrder($holder, '2026-09-02 10:00', ['Hair Oil' => 2, 'Ortho Oil' => 1], 300);
    historyOrder($holder, '2026-09-09 10:00', ['Hair Oil' => 3], 300);

    $products = (new FranchiseOrderHistory($holder))->products();

    expect($products->pluck('name')->all())->toBe(['Hair Oil', 'Ortho Oil'])
        ->and((int) $products[0]->units)->toBe(5);
});

test('a sales manager sees the date wise and month wise history of an assigned franchise', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['sales_manager_id' => $manager->id]);

    $august = historyOrder($holder, '2026-08-05 10:00', ['Hair Oil' => 2], 200);
    $september = historyOrder($holder, '2026-09-02 10:00', ['Ortho Oil' => 4], 500);

    $this->actingAs($manager)
        ->get(route('sales.franchise-holders.show', $holder))
        ->assertOk()
        ->assertSee('Purchase history')
        ->assertSeeInOrder(['Month wise', 'September 2026', 'August 2026'])
        ->assertSee(route('sales.orders.show', $august))
        ->assertSee(route('sales.orders.show', $september))
        ->assertSee('02 Sep 2026')
        ->assertSee('Hair Oil')
        ->assertSee('Ortho Oil')
        ->assertSee('₹700.00');
});

test('the franchise page narrows to a chosen month', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['sales_manager_id' => $manager->id]);

    $august = historyOrder($holder, '2026-08-05 10:00', ['Hair Oil' => 2], 200);
    $september = historyOrder($holder, '2026-09-02 10:00', ['Ortho Oil' => 4], 500);

    Livewire::actingAs($manager)
        ->test('pages::sales.franchise-holders.show', ['user' => $holder])
        ->set('month', '2026-08')
        ->assertSee(route('sales.orders.show', $august))
        ->assertDontSee(route('sales.orders.show', $september))
        ->assertSee('Showing August 2026.')
        ->set('month', '')
        ->assertSee(route('sales.orders.show', $september));
});

test('selecting a franchise on the sales dashboard shows its history', function () {
    $manager = User::factory()->salesManager()->create();
    $pune = User::factory()->create(['business_name' => 'Pune Wellness', 'sales_manager_id' => $manager->id]);
    $nagpur = User::factory()->create(['business_name' => 'Nagpur Herbals', 'sales_manager_id' => $manager->id]);

    $puneOrder = historyOrder($pune, '2026-09-02 10:00', ['Pune Special Oil' => 3], 300);
    $nagpurOrder = historyOrder($nagpur, '2026-09-03 10:00', ['Nagpur Special Oil' => 2], 200);

    Livewire::actingAs($manager)
        ->test('pages::sales.dashboard')
        ->assertDontSee('Purchase history')
        ->set('franchise', (string) $pune->id)
        ->assertSee('Purchase history')
        ->assertSee('Month wise')
        ->assertSee('Pune Special Oil')
        ->assertSee(route('sales.orders.show', $puneOrder))
        ->assertDontSee('Nagpur Special Oil')
        ->assertDontSee(route('sales.orders.show', $nagpurOrder))
        ->set('franchise', '')
        ->assertDontSee('Purchase history')
        ->assertSee(route('sales.orders.show', $nagpurOrder));
});

test('the sales dashboard will not open the history of a franchise that is not assigned to the manager', function () {
    $manager = User::factory()->salesManager()->create();
    $stranger = User::factory()->create(['sales_manager_id' => User::factory()->salesManager()->create()->id]);

    historyOrder($stranger, '2026-09-02 10:00', ['Secret Oil' => 1], 100);

    Livewire::actingAs($manager)
        ->test('pages::sales.dashboard')
        ->set('franchise', (string) $stranger->id)
        ->assertDontSee('Purchase history')
        ->assertDontSee('Secret Oil');
});

test('picking another franchise on the dashboard clears the chosen month', function () {
    $manager = User::factory()->salesManager()->create();
    $first = User::factory()->create(['sales_manager_id' => $manager->id]);
    $second = User::factory()->create(['sales_manager_id' => $manager->id]);

    Livewire::actingAs($manager)
        ->test('pages::sales.dashboard')
        ->set('franchise', (string) $first->id)
        ->set('month', '2026-08')
        ->set('franchise', (string) $second->id)
        ->assertSet('month', '');
});

test('an admin can open any franchise history from the dashboard', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create(['business_name' => 'Pune Wellness', 'city' => 'Pune']);
    $other = User::factory()->create(['business_name' => 'Nagpur Herbals']);

    $order = historyOrder($holder, '2026-09-02 10:00', ['Pune Special Oil' => 3], 300);
    historyOrder($other, '2026-09-03 10:00', ['Nagpur Special Oil' => 2], 200);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Franchise order history')
        ->assertSee('Pune Wellness (Pune)')
        ->assertDontSee('Purchase history');

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->set('franchise', (string) $holder->id)
        ->assertSee('Purchase history')
        ->assertSeeInOrder(['Month wise', 'September 2026'])
        ->assertSee('Pune Special Oil')
        ->assertSee(route('admin.orders.show', $order))
        ->assertDontSee('Nagpur Special Oil')
        ->assertSee(route('admin.franchise-holders.show', $holder));
});

test('the admin dashboard ignores a franchise id that is not a franchise holder', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.dashboard')
        ->set('franchise', (string) $manager->id)
        ->assertDontSee('Purchase history');
});

test('the admin franchise page shows the same month wise history', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create();

    $order = historyOrder($holder, '2026-09-02 10:00', ['Ortho Oil' => 4], 500);
    $unpaid = Order::factory()->create(['user_id' => $holder->id]);

    $this->actingAs($admin)
        ->get(route('admin.franchise-holders.show', $holder))
        ->assertOk()
        ->assertSeeInOrder(['Month wise', 'September 2026'])
        ->assertSee('Ortho Oil')
        ->assertSee(route('admin.orders.show', $order))
        ->assertDontSee(route('admin.orders.show', $unpaid));
});

test('the history says so when a franchise has not ordered yet', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.franchise-holders.show', $holder))
        ->assertOk()
        ->assertSee('No orders yet.')
        ->assertSee('No orders found.');
});
