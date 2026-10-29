<?php

use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

test('non-admins cannot access the admin packages page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.packages.index'))->assertForbidden();
});

test('admin can create a package with products', function () {
    $admin = User::factory()->admin()->create();
    $productA = Product::factory()->create(['price' => 100]);
    $productB = Product::factory()->create(['price' => 50]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('name', 'Starter Wellness Kit')
        ->set('description', 'A curated starter kit.')
        ->set('products', [
            ['product_id' => (string) $productA->id, 'quantity' => 2],
            ['product_id' => (string) $productB->id, 'quantity' => 1],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $package = Package::where('name', 'Starter Wellness Kit')->firstOrFail();

    expect($package->slug)->toBe('starter-wellness-kit');
    expect($package->products)->toHaveCount(2);
    expect($package->products->firstWhere('id', $productA->id)->pivot->quantity)->toBe(2);

    // 2 x 100 + 1 x 50, no discount.
    expect((float) $package->subtotal)->toBe(250.0)
        ->and((float) $package->price)->toBe(250.0);
});

test('admin can update a package and its products', function () {
    $admin = User::factory()->admin()->create();
    $package = Package::factory()->create();
    $productA = Product::factory()->create();
    $package->products()->attach($productA, ['quantity' => 1]);
    $productB = Product::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.edit', ['package' => $package])
        ->set('products', [
            ['product_id' => (string) $productB->id, 'quantity' => 3],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $package->refresh();

    expect($package->products)->toHaveCount(1);
    expect($package->products->first()->id)->toBe($productB->id);
    expect($package->products->first()->pivot->quantity)->toBe(3);
});

test('admin can delete a package with no orders', function () {
    $admin = User::factory()->admin()->create();
    $package = Package::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.index')
        ->call('delete', $package);

    expect(Package::find($package->id))->toBeNull();
});
