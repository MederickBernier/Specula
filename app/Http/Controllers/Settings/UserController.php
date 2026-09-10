<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserAccessRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Account management for administrators.
 *
 * Administrator accounts are deliberately not editable here: they are created
 * with `clearsight:create-user --admin` and changed the same way. Keeping them out
 * of the UI removes every path by which the last administrator could lock
 * themselves out of their own instance.
 */
class UserController extends Controller
{
    /**
     * List the accounts on this instance.
     */
    public function index(): Response
    {
        return Inertia::render('settings/users', [
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'is_admin', 'is_read_only', 'created_at']),
        ]);
    }

    /**
     * Create an account for someone else.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User;
        $user->name = $request->validated('name');
        $user->email = $request->validated('email');
        $user->password = $request->validated('password');
        $user->is_read_only = $request->boolean('is_read_only');
        $user->email_verified_at = now();
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account created.')]);

        return to_route('users.index');
    }

    /**
     * Change what an account is allowed to do.
     */
    public function update(UpdateUserAccessRequest $request, User $user): RedirectResponse
    {
        $this->refuseToTouch($user, $request->user());

        $user->is_read_only = $request->boolean('is_read_only');
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Access updated.')]);

        return to_route('users.index');
    }

    /**
     * Remove an account.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->refuseToTouch($user, request()->user());

        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account removed.')]);

        return to_route('users.index');
    }

    /**
     * Two accounts this page will not act on, both to avoid a lockout: your own,
     * and any other administrator.
     */
    private function refuseToTouch(User $user, ?User $actor): void
    {
        abort_if($user->is($actor), 403, __('Change your own account in your own settings.'));
        abort_if($user->is_admin, 403, __('Administrator accounts are managed with the console command.'));
    }
}
