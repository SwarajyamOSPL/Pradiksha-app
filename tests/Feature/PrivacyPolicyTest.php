<?php

use App\Models\Package;
use App\Models\Product;
use App\Models\User;

test('the privacy policy page is public', function () {
    $this->get(route('policy.privacy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('At a glance');

    expect(route('policy.privacy', absolute: false))->toBe('/privacy-policy');

    $this->actingAs(User::factory()->create())->get(route('policy.privacy'))->assertOk();
});

test('the privacy policy explains what is collected, shared and kept', function () {
    $this->get(route('policy.privacy'))
        ->assertOk()
        ->assertSeeInOrder([
            '1. Who we are',
            '2. Information we collect',
            '3. How we use your information',
            '4. Who we share it with',
            '5. Cookies',
            '6. How long we keep it',
            '7. Keeping your information safe',
            '8. Your choices',
            '9. Children',
            '10. How to contact us',
            '11. Changes to this policy',
        ])
        ->assertSee('delivery address')
        ->assertSee('Razorpay')
        ->assertSee('Courier and delivery partners')
        ->assertSee('Your assigned sales manager and our admin team');
});

test('the privacy policy makes its key promises plainly', function () {
    $this->get(route('policy.privacy'))
        ->assertOk()
        ->assertSee('We never see or store your card, UPI or bank details')
        ->assertSee('We do not sell your personal information.')
        ->assertSee('We do not use advertising or tracking cookies.')
        ->assertSee('stored in hashed form');
});

test('the privacy policy uses the company name from the invoice settings', function () {
    config(['invoice.company.name' => 'Pradiksha Herbal Pvt Ltd']);

    $this->get(route('policy.privacy'))->assertOk()->assertSee('mean Pradiksha Herbal Pvt Ltd.');
});

test('the privacy policy shows contact details when configured and works without them', function () {
    config(['invoice.company.email' => 'support@pradikshaherbal.example', 'invoice.company.phone' => '+91 98765 43210']);

    $this->get(route('policy.privacy'))
        ->assertOk()
        ->assertSee('mailto:support@pradikshaherbal.example', false)
        ->assertSee('tel:+91 98765 43210', false);

    config(['invoice.company.email' => null, 'invoice.company.phone' => null]);

    $this->get(route('policy.privacy'))->assertOk()->assertDontSee('mailto:', false)->assertSee('contact the company or your assigned sales manager');
});

test('the footer of every shop page links to both policies', function () {
    foreach (['home', 'franchise', 'packages.index', 'products.index', 'policy.privacy', 'policy.refund'] as $page) {
        $this->get(route($page))
            ->assertOk()
            ->assertSee('href="'.route('policy.privacy').'"', false)
            ->assertSee('href="'.route('policy.refund').'"', false);
    }
});

test('the registration form links to both policies', function () {
    $package = Package::factory()->create(['slug' => 'basic', 'name' => 'Basic']);
    $package->products()->attach(Product::factory()->create(['stock' => 50]), ['quantity' => 1]);

    $this->get(route('register', ['package' => 'basic']))
        ->assertOk()
        ->assertSee('By registering you agree to our')
        ->assertSee('href="'.route('policy.privacy').'"', false)
        ->assertSee('href="'.route('policy.refund').'"', false);
});

test('both policy pages share the same layout and sections', function () {
    foreach (['policy.refund', 'policy.privacy'] as $page) {
        $this->get(route($page))
            ->assertOk()
            ->assertSee('Last updated October 2026')
            ->assertSee('At a glance')
            ->assertSee('How to contact us')
            ->assertSee('Changes to this policy');
    }
});
