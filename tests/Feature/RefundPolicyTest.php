<?php

use App\Models\Package;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('the refund and return policy page is public', function () {
    $this->get(route('policy.refund'))
        ->assertOk()
        ->assertSee('Refund & Return Policy')
        ->assertSee('At a glance');

    expect(route('policy.refund', absolute: false))->toBe('/refund-policy');

    $this->actingAs(User::factory()->create())->get(route('policy.refund'))->assertOk();
});

test('the policy states the package, cancellation, damage and expiry rules', function () {
    $this->get(route('policy.refund'))
        ->assertOk()
        ->assertSee('Once a package has been purchased, it will not be refunded.')
        ->assertSee('within 24 hours of placing that order')
        ->assertSee('inform the company within 24 hours of receiving the goods')
        ->assertSee('Broken or damaged goods will be replaced.')
        ->assertSee('Products that reach their expiry date are not accepted as returns.');
});

test('the policy covers the extra cases and is numbered with a contact section', function () {
    $this->get(route('policy.refund'))
        ->assertOk()
        ->assertSeeInOrder([
            '1. Package purchases',
            '2. Cancelling an order',
            '3. Broken or damaged products',
            '4. Wrong or missing items',
            '5. Expired products',
            '6. What we cannot take back',
            '7. Payment problems',
            '8. How to contact us',
            '9. Changes to this policy',
        ])
        ->assertSee('has already been dispatched cannot be cancelled')
        ->assertSee('within 7 working days');
});

test('the policy shows the company contact details when they are configured', function () {
    config(['invoice.company.email' => 'support@pradikshaherbal.example', 'invoice.company.phone' => '+91 98765 43210']);

    $this->get(route('policy.refund'))
        ->assertOk()
        ->assertSee('mailto:support@pradikshaherbal.example', false)
        ->assertSee('tel:+91 98765 43210', false);
});

test('the policy still works without any company contact details', function () {
    config(['invoice.company.email' => null, 'invoice.company.phone' => null]);

    $this->get(route('policy.refund'))
        ->assertOk()
        ->assertSee('contact the company or your assigned sales manager')
        ->assertDontSee('mailto:', false);
});

test('the footer of every shop page links to the policy', function () {
    foreach (['home', 'franchise', 'packages.index', 'products.index', 'policy.refund'] as $page) {
        $this->get(route($page))->assertOk()->assertSee('href="'.route('policy.refund').'"', false);
    }
});

test('the payment pages remind the buyer of the policy', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['price' => 100]);
    $package = Package::factory()->create();
    $package->products()->attach($product, ['quantity' => 1]);

    session(['cart' => [$product->id => 1]]);

    Livewire::actingAs($user)
        ->test('pages::shop.package-checkout', ['package' => $package])
        ->assertSee('A package purchase is final and is not refunded.')
        ->assertSee(route('policy.refund'), false);

    Livewire::actingAs($user)
        ->test('pages::shop.checkout')
        ->assertSee('You can cancel this order within 24 hours of placing it.')
        ->assertSee(route('policy.refund'), false);
});
