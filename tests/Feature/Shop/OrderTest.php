<?php

use App\Models\Order;
use App\Models\User;

test('a franchise holder sees their own orders in the order history', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('#'.$order->id);
});

test('a franchise holder can view their own order details', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $user->id, 'notes' => 'Leave at the front desk']);

    $this->actingAs($user)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Leave at the front desk');
});

test('a franchise holder cannot view another users order', function () {
    $user = User::factory()->create();
    $otherUsersOrder = Order::factory()->create();

    $this->actingAs($user)
        ->get(route('orders.show', $otherUsersOrder))
        ->assertForbidden();
});
