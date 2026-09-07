<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('it seeds the admin account', function () {
    $this->seed();

    $user = User::where('email', 'mederick.bernier@hotmail.ca')->firstOrFail();

    expect($user->name)->toBe('mederick.bernier')
        ->and($user->is_admin)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('12345', $user->password))->toBeTrue();
});

test('it does not seed a second account when run twice', function () {
    $this->seed();
    $this->seed();

    expect(User::count())->toBe(1);
});

test('it refuses to seed in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    (new DatabaseSeeder)->run();

    expect(User::count())->toBe(0);
});
