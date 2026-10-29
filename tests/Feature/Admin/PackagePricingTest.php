<?php

use App\Enums\DiscountType;
use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

test('price breakdown applies a percentage discount', function () {
    expect(Package::priceBreakdown(1000, DiscountType::Percent, 15))
        ->toBe(['subtotal' => 1000.0, 'discount' => 150.0, 'price' => 850.0]);
});

test('price breakdown applies a fixed discount', function () {
    expect(Package::priceBreakdown(1000, DiscountType::Fixed, 120))
        ->toBe(['subtotal' => 1000.0, 'discount' => 120.0, 'price' => 880.0]);
});

test('price breakdown never goes below zero or discounts more than the subtotal', function () {
    expect(Package::priceBreakdown(300, DiscountType::Fixed, 5000))
        ->toBe(['subtotal' => 300.0, 'discount' => 300.0, 'price' => 0.0])
        ->and(Package::priceBreakdown(300, DiscountType::Percent, 250)['price'])->toBe(0.0)
        ->and(Package::priceBreakdown(300, null, 50))->toBe(['subtotal' => 300.0, 'discount' => 0.0, 'price' => 300.0]);
});

test('admin creates a package whose price is calculated from its products and a discount', function () {
    $admin = User::factory()->admin()->create();
    $productA = Product::factory()->create(['price' => 199.50]);
    $productB = Product::factory()->create(['price' => 80]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('name', 'Immunity Combo')
        ->set('products', [
            ['product_id' => (string) $productA->id, 'quantity' => 2],
            ['product_id' => (string) $productB->id, 'quantity' => 3],
        ])
        ->set('discount_type', 'percent')
        ->set('discount_value', '10')
        ->call('save')
        ->assertHasNoErrors();

    $package = Package::where('slug', 'immunity-combo')->firstOrFail();

    // 2 x 199.50 + 3 x 80 = 639.00, less 10% = 575.10.
    expect((float) $package->subtotal)->toBe(639.0)
        ->and($package->discount_type)->toBe(DiscountType::Percent)
        ->and((float) $package->discount_value)->toBe(10.0)
        ->and((float) $package->price)->toBe(575.1)
        ->and($package->discountAmount())->toBe(63.9)
        ->and($package->discountPercentage())->toBe(10);
});

test('the admin form shows the live package price summary', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('products', [['product_id' => (string) $product->id, 'quantity' => 4]])
        ->set('discount_type', 'fixed')
        ->set('discount_value', '50')
        ->assertSee('₹400.00')
        ->assertSee('-₹50.00')
        ->assertSee('₹350.00');
});

test('the admin form warns about products that have no price', function () {
    $admin = User::factory()->admin()->create();
    $unpriced = Product::factory()->create(['name' => 'Mystery Tonic', 'price' => null]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('products', [['product_id' => (string) $unpriced->id, 'quantity' => 1]])
        ->assertSee('Some products have no price yet');
});

test('a percentage discount cannot exceed 100', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.create')
        ->set('name', 'Too Generous')
        ->set('products', [['product_id' => (string) $product->id, 'quantity' => 1]])
        ->set('discount_type', 'percent')
        ->set('discount_value', '150')
        ->call('save')
        ->assertHasErrors(['discount_value']);

    expect(Package::where('slug', 'too-generous')->exists())->toBeFalse();
});

test('removing the discount type clears the stored discount', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => 100]);
    $package = Package::factory()->create(['discount_type' => DiscountType::Percent, 'discount_value' => 20]);
    $package->products()->attach($product, ['quantity' => 1]);
    $package->refreshPrice();

    expect((float) $package->price)->toBe(80.0);

    Livewire::actingAs($admin)
        ->test('pages::admin.packages.edit', ['package' => $package])
        ->assertSet('discount_type', 'percent')
        ->set('discount_type', '')
        ->call('save')
        ->assertHasNoErrors();

    $package->refresh();

    expect($package->discount_type)->toBeNull()
        ->and((float) $package->discount_value)->toBe(0.0)
        ->and((float) $package->price)->toBe(100.0);
});

test('changing a product price updates the price of every package that contains it', function () {
    $product = Product::factory()->create(['price' => 100]);
    $other = Product::factory()->create(['price' => 10]);

    $package = Package::factory()->create(['discount_type' => DiscountType::Fixed, 'discount_value' => 30]);
    $package->products()->attach([$product->id => ['quantity' => 2], $other->id => ['quantity' => 1]]);
    $package->refreshPrice();

    expect((float) $package->price)->toBe(180.0);

    $product->update(['price' => 150]);

    expect((float) $package->refresh()->subtotal)->toBe(310.0)
        ->and((float) $package->price)->toBe(280.0);
});

test('updating something other than the price leaves package prices alone', function () {
    $product = Product::factory()->create(['price' => 100]);
    $package = Package::factory()->create();
    $package->products()->attach($product, ['quantity' => 1]);
    $package->refreshPrice();
    $package->forceFill(['price' => 12.34])->save();

    $product->update(['name' => 'Renamed']);

    expect((float) $package->refresh()->price)->toBe(12.34);
});

test('admin can set a product price from the products list', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['price' => null]);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.index')
        ->call('updatePrice', $product, '249.50');

    expect((float) $product->refresh()->price)->toBe(249.5);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.index')
        ->call('updatePrice', $product, 'abc');

    expect((float) $product->refresh()->price)->toBe(249.5);
});

test('package prices and discounts are public, while individual product prices stay hidden from guests', function () {
    $package = Package::factory()->create(['name' => 'Festival Pack', 'discount_type' => DiscountType::Percent, 'discount_value' => 20]);
    $product = Product::factory()->create(['name' => 'Festival Oil', 'price' => 500]);
    $package->products()->attach($product, ['quantity' => 1]);
    $package->refreshPrice();

    $this->get(route('packages.show', $package))
        ->assertOk()
        ->assertSee('₹400.00')
        ->assertSee('20% off')
        ->assertSee('Festival Oil');

    $this->get(route('products.show', $product))->assertOk()->assertDontSee('500.00');

    $this->actingAs(User::factory()->create())
        ->get(route('packages.show', $package))
        ->assertOk()
        ->assertSee('₹400.00')
        ->assertSee('₹500.00')
        ->assertSee('20% off');
});
