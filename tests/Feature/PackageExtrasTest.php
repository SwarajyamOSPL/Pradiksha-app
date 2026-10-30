<?php

use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/**
 * @param  array<string, mixed>  $attributes
 */
function packageWithExtras(array $attributes = []): Package
{
    $package = Package::factory()->create([
        'slug' => 'basic',
        'name' => 'Basic',
        'fixed_price' => 5000,
        'price' => 5000,
        'goods_worth' => 9000,
        'inclusions' => ['Canopy', 'T Shirt', 'Visiting Card'],
        ...$attributes,
    ]);
    $package->products()->attach(Product::factory()->create(['stock' => 50]), ['quantity' => 1]);

    return $package;
}

beforeEach(function () {
    Storage::fake('public');
});

test('the packages page shows the goods worth and what else comes with each package', function () {
    packageWithExtras();

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Get ₹9,000 worth of goods')
        ->assertSee('Also included')
        ->assertSee('Canopy')
        ->assertSee('T Shirt')
        ->assertSee('Visiting Card')
        ->assertSee('data-package-inclusions="basic"', false);
});

test('the opened package panel lists the extras too', function () {
    packageWithExtras();

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertSee('Also with the Basic package')
        ->assertSee('₹9,000 worth of goods');
});

test('a package without extras shows no extras section', function () {
    $package = Package::factory()->create(['slug' => 'plain', 'name' => 'Plain', 'goods_worth' => null, 'inclusions' => null]);
    $package->products()->attach(Product::factory()->create(['stock' => 50]), ['quantity' => 1]);

    $this->get(route('packages.index'))
        ->assertOk()
        ->assertDontSee('worth of goods')
        ->assertDontSee('Also included')
        ->assertDontSee('Also with the Plain package');

    $this->get(route('packages.show', $package))->assertOk()->assertDontSee('Also included');
});

test('the package page lists the extras', function () {
    $package = packageWithExtras();

    $this->get(route('packages.show', $package))
        ->assertOk()
        ->assertSee('Get ₹9,000 worth of goods')
        ->assertSeeInOrder(['Also included', 'Canopy', 'T Shirt', "What's included"]);
});

test('the package checkout lists the extras', function () {
    $package = packageWithExtras();
    $holder = User::factory()->create();

    $this->actingAs($holder)
        ->get(route('packages.checkout', $package))
        ->assertOk()
        ->assertSee('Also included with this package')
        ->assertSee('Canopy');
});

test('blank entries in the extras are left out', function () {
    $package = packageWithExtras(['inclusions' => ['Canopy', '', null, 'I Card']]);

    expect($package->inclusionList())->toBe(['Canopy', 'I Card']);
});

test('an admin can save the goods worth and extras of a new package', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('name', 'Mini')
        ->set('fixed_price', '10000')
        ->set('goods_worth', '20000')
        ->set('inclusionsText', "Canopy\n\n  T Shirt  \r\nVisiting Card\n")
        ->set('products', [['product_id' => (string) $product->id, 'quantity' => 1]])
        ->call('save')
        ->assertHasNoErrors();

    $package = Package::where('slug', 'mini')->firstOrFail();

    expect((float) $package->goods_worth)->toBe(20000.0)
        ->and($package->inclusions)->toBe(['Canopy', 'T Shirt', 'Visiting Card']);
});

test('an admin can change and clear the extras of a package', function () {
    $admin = User::factory()->admin()->create();
    $package = packageWithExtras();

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.edit', ['package' => $package])
        ->assertSet('goods_worth', '9000')
        ->assertSet('inclusionsText', "Canopy\nT Shirt\nVisiting Card")
        ->set('goods_worth', '10000')
        ->set('inclusionsText', 'Canopy')
        ->call('save')
        ->assertHasNoErrors();

    expect((float) $package->fresh()->goods_worth)->toBe(10000.0)
        ->and($package->fresh()->inclusions)->toBe(['Canopy']);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.edit', ['package' => $package->fresh()])
        ->set('goods_worth', '')
        ->set('inclusionsText', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($package->fresh()->goods_worth)->toBeNull()
        ->and($package->fresh()->inclusions)->toBe([])
        ->and($package->fresh()->hasGoodsWorth())->toBeFalse();
});

test('the goods worth must be a number', function () {
    $admin = User::factory()->admin()->create();
    $package = packageWithExtras();

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.edit', ['package' => $package])
        ->set('goods_worth', 'lots')
        ->call('save')
        ->assertHasErrors(['goods_worth']);
});
