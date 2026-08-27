<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates a non-admin user by default', function () {
    $this->artisan('specula:create-user', [
        '--name' => 'Mederick',
        '--email' => 'mederick@example.com',
    ])
        ->expectsQuestion('Password', 'password123')
        ->assertSuccessful();

    $user = User::where('email', 'mederick@example.com')->sole();

    expect($user->name)->toBe('Mederick')
        ->and($user->is_admin)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->not->toBe('password123')
        ->and(Hash::check('password123', $user->password))->toBeTrue();
});

test('it grants admin rights with the admin flag', function () {
    $this->artisan('specula:create-user', [
        '--name' => 'Admin',
        '--email' => 'admin@example.com',
        '--admin' => true,
    ])
        ->expectsQuestion('Password', 'password123')
        ->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->sole()->is_admin)->toBeTrue();
});

test('it prompts for name and email when not passed as options', function () {
    $this->artisan('specula:create-user')
        ->expectsQuestion('Name', 'Prompted')
        ->expectsQuestion('Email', 'prompted@example.com')
        ->expectsQuestion('Password', 'password123')
        ->assertSuccessful();

    expect(User::where('email', 'prompted@example.com')->exists())->toBeTrue();
});

test('it rejects a duplicate email without creating a user', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('specula:create-user', [
        '--name' => 'Duplicate',
        '--email' => 'taken@example.com',
    ])
        ->expectsQuestion('Password', 'password123')
        ->assertFailed();

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

test('it rejects a password that fails the default rules', function () {
    $this->artisan('specula:create-user', [
        '--name' => 'Weak',
        '--email' => 'weak@example.com',
    ])
        ->expectsQuestion('Password', 'short')
        ->assertFailed();

    expect(User::where('email', 'weak@example.com')->exists())->toBeFalse();
});
