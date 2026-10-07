<?php

use App\Enums\UserStatus;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

test('non-admins cannot access the franchise holders index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.franchise-holders.index'))->assertForbidden();
});

test('admin can approve a pending franchise holder', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->pending()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.franchise-holders.index')
        ->call('updateStatus', $holder->id, 'approved');

    expect($holder->fresh()->status)->toBe(UserStatus::Approved);
});

test('admin can suspend an approved franchise holder', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.franchise-holders.index')
        ->call('updateStatus', $holder->id, 'suspended');

    expect($holder->fresh()->status)->toBe(UserStatus::Suspended);
});

test('admin can assign a sales manager to a franchise holder', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create();
    $manager = User::factory()->salesManager()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.franchise-holders.show', ['user' => $holder])
        ->set('salesManagerId', $manager->id)
        ->call('assignSalesManager');

    expect($holder->fresh()->sales_manager_id)->toBe($manager->id);
});

test('admin can view a franchise holders order history', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $holder->id]);

    $this->actingAs($admin)
        ->get(route('admin.franchise-holders.show', $holder))
        ->assertOk()
        ->assertSee('#'.$order->id);
});
