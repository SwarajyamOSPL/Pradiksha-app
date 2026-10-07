<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
});

test('non-admins cannot access the admin products page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.products.index'))->assertForbidden();
});

test('admin can create a product with an image', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.products.create')
        ->set('name', 'Herbal Hair Oil')
        ->set('category', 'Hair Oil')
        ->set('description', 'A nourishing herbal hair oil.')
        ->set('price', '299.00')
        ->set('image', UploadedFile::fake()->image('product.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::where('name', 'Herbal Hair Oil')->firstOrFail();

    expect($product->slug)->toBe('herbal-hair-oil');
    expect($product->price)->toEqual('299.00');
    expect($product->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($product->image_path);
});

test('admin can update a product', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create(['name' => 'Old Name', 'price' => 100]);

    Livewire::actingAs($admin)
        ->test('pages::admin.products.edit', ['product' => $product])
        ->set('name', 'New Name')
        ->set('price', '150.00')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->fresh()->name)->toBe('New Name');
    expect($product->fresh()->price)->toEqual('150.00');
});

test('admin can delete a product with no orders', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.products.index')
        ->call('delete', $product);

    expect(Product::find($product->id))->toBeNull();
});
