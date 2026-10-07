<?php

use App\Models\Order;
use App\Models\User;

test('non-sales-managers cannot access the sales dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('sales.dashboard'))->assertForbidden();
});

test('a sales manager only sees their assigned franchise holders on the dashboard', function () {
    $manager = User::factory()->salesManager()->create();
    $otherManager = User::factory()->salesManager()->create();

    $assigned = User::factory()->create(['name' => 'Assigned Holder', 'sales_manager_id' => $manager->id]);
    User::factory()->create(['name' => 'Unassigned Holder', 'sales_manager_id' => $otherManager->id]);

    $this->actingAs($manager)
        ->get(route('sales.dashboard'))
        ->assertOk()
        ->assertSee('Assigned Holder')
        ->assertDontSee('Unassigned Holder');
});

test('a sales manager can view an assigned franchise holders order history', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['sales_manager_id' => $manager->id]);
    $order = Order::factory()->create(['user_id' => $holder->id]);

    $this->actingAs($manager)
        ->get(route('sales.franchise-holders.show', $holder))
        ->assertOk()
        ->assertSee('#'.$order->id);
});

test('a sales manager cannot view a franchise holder not assigned to them', function () {
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create();

    $this->actingAs($manager)
        ->get(route('sales.franchise-holders.show', $holder))
        ->assertForbidden();
});

test('sales managers are redirected from the generic dashboard to their own', function () {
    $manager = User::factory()->salesManager()->create();

    $this->actingAs($manager)
        ->get(route('dashboard'))
        ->assertRedirect(route('sales.dashboard'));
});
