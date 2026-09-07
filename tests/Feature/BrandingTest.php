<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('the application is named Specula', function () {
    expect(config('app.name'))->toBe('Specula');
});

test('the name reaches the frontend on every page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('welcome')
            ->where('name', 'Specula'));

    $this->actingAs(User::factory()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('name', 'Specula'));
});

test('the landing page points a signed-out visitor at the way in', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('auth.user', null));
});

test('the landing page is reachable while signed in', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('welcome')
            ->where('auth.user.name', fn (string $name) => $name !== ''));
});
