<?php

use App\Models\User;
use Livewire\Livewire;

test('non-admins cannot open the franchise assignment page', function () {
    $manager = User::factory()->salesManager()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.sales-managers.franchises', $manager))
        ->assertForbidden();
});

test('the assignment page only works for sales managers', function () {
    $admin = User::factory()->admin()->create();
    $holder = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.sales-managers.franchises', $holder))
        ->assertNotFound();
});

test('creating a sales manager takes the admin straight to assigning franchises', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.create')
        ->set('name', 'Asha Verma')
        ->set('email', 'asha@example.com')
        ->set('phone', '9876543210')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('save')
        ->assertRedirect(route('admin.sales-managers.franchises', User::where('email', 'asha@example.com')->first()));
});

test('the city list contains each city once, ignoring case and spacing', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    User::factory()->create(['city' => 'Pune']);
    User::factory()->create(['city' => ' pune ']);
    User::factory()->create(['city' => 'Nagpur']);
    User::factory()->salesManager()->create(['city' => 'Mumbai']);

    $html = Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->html();

    expect(substr_count($html, '>Pune<'))->toBe(1)
        ->and($html)->toContain('>Nagpur<')
        ->and($html)->not->toContain('>Mumbai<');
});

test('no franchises are listed until a city is chosen, then only that citys franchises appear', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    User::factory()->create(['business_name' => 'Pune Wellness', 'city' => 'Pune']);
    User::factory()->create(['business_name' => 'Nagpur Herbals', 'city' => 'Nagpur']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->assertDontSee('Select franchises in')
        ->assertDontSee('Pune Wellness')
        ->set('city', 'pune')
        ->assertSee('Pune Wellness')
        ->assertDontSee('Nagpur Herbals');
});

test('admin can assign several franchises in a city to a sales manager at once', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    $a = User::factory()->create(['city' => 'Pune']);
    $b = User::factory()->create(['city' => 'Pune']);
    $c = User::factory()->create(['city' => 'Pune']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->set('city', 'pune')
        ->set('selected', [(string) $a->id, (string) $b->id])
        ->call('save');

    expect($a->refresh()->sales_manager_id)->toBe($manager->id)
        ->and($b->refresh()->sales_manager_id)->toBe($manager->id)
        ->and($c->refresh()->sales_manager_id)->toBeNull();
});

test('select all ticks every franchise in the chosen city', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    $puneOne = User::factory()->create(['city' => 'Pune']);
    $puneTwo = User::factory()->create(['city' => 'Pune']);
    $nagpur = User::factory()->create(['city' => 'Nagpur']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->set('city', 'pune')
        ->call('selectAll')
        ->call('save');

    expect($puneOne->refresh()->sales_manager_id)->toBe($manager->id)
        ->and($puneTwo->refresh()->sales_manager_id)->toBe($manager->id)
        ->and($nagpur->refresh()->sales_manager_id)->toBeNull();
});

test('choosing a city pre-ticks the franchises already assigned to this manager', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();
    $other = User::factory()->salesManager()->create();

    $mine = User::factory()->create(['city' => 'Pune', 'sales_manager_id' => $manager->id]);
    User::factory()->create(['city' => 'Pune', 'sales_manager_id' => $other->id]);
    User::factory()->create(['city' => 'Pune']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->set('city', 'pune')
        ->assertSet('selected', [(string) $mine->id]);
});

test('unticking a franchise releases it but leaves other cities untouched', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    $kept = User::factory()->create(['city' => 'Pune', 'sales_manager_id' => $manager->id]);
    $dropped = User::factory()->create(['city' => 'Pune', 'sales_manager_id' => $manager->id]);
    $elsewhere = User::factory()->create(['city' => 'Nagpur', 'sales_manager_id' => $manager->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->set('city', 'pune')
        ->set('selected', [(string) $kept->id])
        ->call('save');

    expect($kept->refresh()->sales_manager_id)->toBe($manager->id)
        ->and($dropped->refresh()->sales_manager_id)->toBeNull()
        ->and($elsewhere->refresh()->sales_manager_id)->toBe($manager->id);
});

test('ticking a franchise held by another manager moves it, and unticking never steals from others', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();
    $other = User::factory()->salesManager()->create();

    $moved = User::factory()->create(['city' => 'Pune', 'sales_manager_id' => $other->id]);
    $left = User::factory()->create(['city' => 'Pune', 'sales_manager_id' => $other->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->set('city', 'pune')
        ->set('selected', [(string) $moved->id])
        ->call('save');

    expect($moved->refresh()->sales_manager_id)->toBe($manager->id)
        ->and($left->refresh()->sales_manager_id)->toBe($other->id);
});

test('tampered ids from another city or a non franchise user are ignored', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    $inCity = User::factory()->create(['city' => 'Pune']);
    $otherCity = User::factory()->create(['city' => 'Nagpur']);
    $someAdmin = User::factory()->admin()->create(['city' => 'Pune']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->set('city', 'pune')
        ->set('selected', [(string) $inCity->id, (string) $otherCity->id, (string) $someAdmin->id])
        ->call('save');

    expect($inCity->refresh()->sales_manager_id)->toBe($manager->id)
        ->and($otherCity->refresh()->sales_manager_id)->toBeNull()
        ->and($someAdmin->refresh()->sales_manager_id)->toBeNull();
});

test('admin can remove a single assigned franchise from the assigned list', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();
    $holder = User::factory()->create(['city' => 'Pune', 'sales_manager_id' => $manager->id]);
    $heldByOther = User::factory()->create(['sales_manager_id' => User::factory()->salesManager()->create()->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->call('release', $holder->id)
        ->call('release', $heldByOther->id);

    expect($holder->refresh()->sales_manager_id)->toBeNull()
        ->and($heldByOther->refresh()->sales_manager_id)->not->toBeNull();
});

test('the assigned list shows franchises from every city', function () {
    $admin = User::factory()->admin()->create();
    $manager = User::factory()->salesManager()->create();

    User::factory()->create(['business_name' => 'Pune Wellness', 'city' => 'Pune', 'sales_manager_id' => $manager->id]);
    User::factory()->create(['business_name' => 'Nagpur Herbals', 'city' => 'Nagpur', 'sales_manager_id' => $manager->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales-managers.franchises', ['salesManager' => $manager])
        ->assertSee('Currently assigned (2)')
        ->assertSee('Pune Wellness')
        ->assertSee('Nagpur Herbals');
});
