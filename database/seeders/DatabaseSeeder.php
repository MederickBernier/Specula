<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * The seeded password is deliberately weak and is for local development only —
     * production accounts are created with `php artisan clearsight:create-user`.
     */
    public function run(): void
    {
        if (App::isProduction()) {
            return;
        }

        User::updateOrCreate(
            ['email' => 'mederick.bernier@hotmail.ca'],
            [
                'name' => 'mederick.bernier',
                'password' => '12345',
                'is_admin' => true,
                'email_verified_at' => now(),
            ],
        );
    }
}
