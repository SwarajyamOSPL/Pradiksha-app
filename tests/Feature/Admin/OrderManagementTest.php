<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

test('non-admins cannot access the admin orders index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.orders.index'))->assertForbidden();
});

test('admin sees orders from all franchise holders', function () {
    $admin = User::factory()->admin()->create();
    $orderA = Order::factory()->create();
    $orderB = Order::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertOk()
        ->assertSee('#'.$orderA->id)
        ->assertSee('#'.$orderB->id);
});

test('admin can filter orders by status', function () {
    $admin = User::factory()->admin()->create();
    $pending = Order::factory()->create(['status' => OrderStatus::Pending]);
    $delivered = Order::factory()->create(['status' => OrderStatus::Delivered]);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.index')
        ->set('status', 'delivered')
        ->assertSee('#'.$delivered->id)
        ->assertDontSee('#'.$pending->id);
});

test('admin can update an orders status and payment status', function () {
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->create(['status' => OrderStatus::Pending, 'payment_status' => PaymentStatus::Pending]);

    Livewire::actingAs($admin)
        ->test('pages::admin.orders.show', ['order' => $order])
        ->set('status', 'shipped')
        ->set('paymentStatus', 'paid')
        ->call('updateOrder');

    $order->refresh();

    expect($order->status)->toBe(OrderStatus::Shipped);
    expect($order->payment_status)->toBe(PaymentStatus::Paid);
});
