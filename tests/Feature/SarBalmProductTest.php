<?php

use App\Models\Product;
use App\Models\User;
use Database\Seeders\SarBalmProductSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('the seeder adds the SAR Balm product with its photo', function () {
    (new SarBalmProductSeeder)->run();

    $product = Product::where('slug', 'sar-balm')->firstOrFail();

    expect($product->name)->toBe('SAR Balm')
        ->and($product->category)->toBe('Pain Relief')
        ->and($product->description)->toContain('20 g')
        ->and((float) $product->price)->toBe(95.0)
        ->and($product->stock)->toBe(100)
        ->and($product->is_active)->toBeTrue()
        ->and($product->image_path)->toBe('products/sar-balm.webp');

    Storage::disk('public')->assertExists('products/sar-balm.webp');
});

test('the SAR Balm photo shipped with the seeder is a real image', function () {
    $path = database_path('seeders/assets/sar-balm.webp');

    expect($path)->toBeFile();

    [$width, $height, $type] = getimagesize($path);

    expect($type)->toBe(IMAGETYPE_WEBP)->and($width)->toBeGreaterThan(500)->and($height)->toBeGreaterThan(400);
});

test('running the seeder again does not duplicate or overwrite the product', function () {
    (new SarBalmProductSeeder)->run();

    Product::where('slug', 'sar-balm')->update(['price' => 120, 'stock' => 7, 'name' => 'SAR Balm 20g']);

    (new SarBalmProductSeeder)->run();

    expect(Product::where('slug', 'sar-balm')->count())->toBe(1);

    $product = Product::where('slug', 'sar-balm')->firstOrFail();

    expect((float) $product->price)->toBe(120.0)
        ->and($product->stock)->toBe(7)
        ->and($product->name)->toBe('SAR Balm 20g');
});

test('SAR Balm shows up on the products page for a franchise holder with its price and stock', function () {
    (new SarBalmProductSeeder)->run();

    $this->actingAs(User::factory()->create())
        ->get(route('products.index'))
        ->assertOk()
        ->assertSee('SAR Balm')
        ->assertSee('₹95.00')
        ->assertSee('In stock: 100');
});

test('the SAR Balm product page works and shows the photo', function () {
    (new SarBalmProductSeeder)->run();

    $this->actingAs(User::factory()->create())
        ->get(route('products.show', 'sar-balm'))
        ->assertOk()
        ->assertSee('SAR Balm')
        ->assertSee('products/sar-balm.webp', false)
        ->assertSee('Add to Basket');
});

test('SAR Balm is a catalogue product only and has no slide in the home page carousel', function () {
    (new SarBalmProductSeeder)->run();

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('images/sliders/sar-balm.webp', false);

    expect(public_path('images/sliders/sar-balm.webp'))->not->toBeFile();
});
