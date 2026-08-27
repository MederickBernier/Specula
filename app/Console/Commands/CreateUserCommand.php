<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('specula:create-user
            {--name= : The user\'s full name}
            {--email= : The user\'s email address}
            {--admin : Grant account-creation rights}')]
#[Description('Create a Specula user account')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $name = $this->option('name') ?: text('Name', required: true);
        $email = $this->option('email') ?: text('Email', required: true);
        $plainPassword = password('Password', required: true);

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $plainPassword,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::default()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = $plainPassword;
        $user->is_admin = (bool) $this->option('admin');
        $user->email_verified_at = now();
        $user->save();

        $this->info("Created {$user->email}".($user->is_admin ? ' (admin)' : ''));

        return self::SUCCESS;
    }
}
