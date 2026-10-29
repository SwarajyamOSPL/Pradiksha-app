<?php

use App\Models\User;

dataset('dashboards', [
    'admin' => [fn () => User::factory()->admin()->create(), 'admin.dashboard'],
    'sales manager' => [fn () => User::factory()->salesManager()->create(), 'sales.dashboard'],
    'franchise holder' => [fn () => User::factory()->create(), 'dashboard'],
]);

test('no dashboard shows links to the starter kit repository or documentation', function (Closure $makeUser, string $route) {
    $html = $this->actingAs($makeUser())->get(route($route))->assertOk()->getContent();

    expect($html)
        ->not->toContain('Repository')
        ->not->toContain('Documentation')
        ->not->toContain('github.com/laravel')
        ->not->toContain('laravel.com/docs');
})->with('dashboards');

test('admins and sales managers do not get a duplicate platform dashboard link', function () {
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk()->assertDontSee('Platform');
    $this->actingAs(User::factory()->salesManager()->create())->get(route('sales.dashboard'))->assertOk()->assertDontSee('Platform');
});

test('franchise holders get a short menu of their own with no admin options', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My Franchise')
        ->assertSeeInOrder(['Dashboard', 'Products', 'Cart', 'Order History'])
        ->assertSee(route('member.products'), false)
        ->assertDontSee('Franchise Holders')
        ->assertDontSee('Sales Managers')
        ->assertDontSee('Overview')
        ->assertDontSee(route('admin.dashboard'), false)
        ->assertDontSee(route('sales.dashboard'), false)
        ->assertDontSee(route('packages.index'), false);
});

test('the settings pages share the cleaned menu', function (Closure $makeUser) {
    $user = $makeUser();

    foreach (['profile.edit', 'security.edit', 'appearance.edit'] as $routeName) {
        $html = $this->actingAs($user)->get(route($routeName))->assertOk()->getContent();

        expect($html)
            ->not->toContain('Repository')
            ->not->toContain('Documentation')
            ->not->toContain('github.com/laravel');
    }
})->with([
    'admin' => [fn () => User::factory()->admin()->create()],
    'sales manager' => [fn () => User::factory()->salesManager()->create()],
    'franchise holder' => [fn () => User::factory()->create()],
]);
