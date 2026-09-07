<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('only an administrator can reach account management', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('users.index'))->assertForbidden();

    auth()->logout();
    $this->actingAs(User::factory()->readOnly()->create());
    $this->get(route('users.index'))->assertForbidden();

    auth()->logout();
    $this->actingAs(User::factory()->admin()->create());
    $this->get(route('users.index'))->assertOk();
});

test('guests are sent to the login page', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('it lists the accounts with their access level', function () {
    $this->actingAs(User::factory()->admin()->create(['name' => 'Owner']));
    User::factory()->readOnly()->create(['name' => 'Reader']);

    $this->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/users')
            ->has('users', 2)
            ->where('users.0.name', 'Owner')
            ->where('users.0.is_admin', true)
            ->where('users.1.name', 'Reader')
            ->where('users.1.is_read_only', true));
});

test('an administrator can create a read-only account', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('users.store'), [
        'name' => 'Reader',
        'email' => 'reader@example.com',
        'password' => 'Str0ng-Password!',
        'password_confirmation' => 'Str0ng-Password!',
        'is_read_only' => true,
    ])->assertRedirect(route('users.index'));

    $user = User::where('email', 'reader@example.com')->sole();

    expect($user->is_read_only)->toBeTrue()
        ->and($user->is_admin)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('a created account can sign in with the password it was given', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('users.store'), [
        'name' => 'Reader',
        'email' => 'reader@example.com',
        'password' => 'Str0ng-Password!',
        'password_confirmation' => 'Str0ng-Password!',
        'is_read_only' => true,
    ]);

    auth()->logout();

    $this->post(route('login'), [
        'email' => 'reader@example.com',
        'password' => 'Str0ng-Password!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

test('it rejects a duplicate email and a mismatched confirmation', function () {
    $this->actingAs(User::factory()->admin()->create());
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('users.store'), [
        'name' => 'Reader',
        'email' => 'taken@example.com',
        'password' => 'Str0ng-Password!',
        'password_confirmation' => 'Str0ng-Password!',
        'is_read_only' => true,
    ])->assertSessionHasErrors('email');

    $this->post(route('users.store'), [
        'name' => 'Reader',
        'email' => 'new@example.com',
        'password' => 'Str0ng-Password!',
        'password_confirmation' => 'different',
        'is_read_only' => true,
    ])->assertSessionHasErrors('password');

    expect(User::count())->toBe(2);
});

test('an administrator can move an account between read-only and full', function () {
    $this->actingAs(User::factory()->admin()->create());
    $reader = User::factory()->readOnly()->create();

    $this->patch(route('users.update', $reader), ['is_read_only' => false])
        ->assertRedirect(route('users.index'));

    expect($reader->refresh()->is_read_only)->toBeFalse();

    $this->patch(route('users.update', $reader), ['is_read_only' => true]);

    expect($reader->refresh()->is_read_only)->toBeTrue();
});

test('an administrator can remove an account', function () {
    $this->actingAs(User::factory()->admin()->create());
    $reader = User::factory()->readOnly()->create();

    $this->delete(route('users.destroy', $reader))->assertRedirect(route('users.index'));

    expect(User::count())->toBe(1);
});

test('an administrator cannot lock themselves out through this page', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->patch(route('users.update', $admin), ['is_read_only' => true])->assertForbidden();
    $this->delete(route('users.destroy', $admin))->assertForbidden();

    expect($admin->refresh()->is_read_only)->toBeFalse()
        ->and(User::count())->toBe(1);
});

test('one administrator cannot demote or delete another', function () {
    $this->actingAs(User::factory()->admin()->create());
    $other = User::factory()->admin()->create();

    $this->patch(route('users.update', $other), ['is_read_only' => true])->assertForbidden();
    $this->delete(route('users.destroy', $other))->assertForbidden();

    expect($other->refresh()->is_read_only)->toBeFalse()
        ->and(User::count())->toBe(2);
});
