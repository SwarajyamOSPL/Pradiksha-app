<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'role:admin'])->get('/__test/admin-only', fn () => 'ok');
    Route::middleware(['web', 'auth', 'role:sales_manager'])->get('/__test/sales-only', fn () => 'ok');
});

test('a franchise holder cannot access an admin-only route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/__test/admin-only')->assertForbidden();
});

test('a sales manager cannot access an admin-only route', function () {
    $user = User::factory()->salesManager()->create();

    $this->actingAs($user)->get('/__test/admin-only')->assertForbidden();
});

test('an admin can access an admin-only route', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->get('/__test/admin-only')->assertOk();
});

test('a sales manager only sees their assigned franchise holders', function () {
    $salesManager = User::factory()->salesManager()->create();
    $otherSalesManager = User::factory()->salesManager()->create();

    $assigned = User::factory()->count(2)->create(['sales_manager_id' => $salesManager->id]);
    User::factory()->count(3)->create(['sales_manager_id' => $otherSalesManager->id]);

    expect($salesManager->franchiseHolders)->toHaveCount(2);
    expect($salesManager->franchiseHolders->pluck('id')->sort()->values()->all())
        ->toBe($assigned->pluck('id')->sort()->values()->all());
});
