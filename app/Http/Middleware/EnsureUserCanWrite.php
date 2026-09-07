<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks read-only accounts from changing anything.
 *
 * This sits on the route group rather than on each controller: every write in
 * the app is an unsafe HTTP method through that group, so one guard covers all
 * of them, including modules added later. Hiding buttons in the UI is a
 * courtesy; this is the actual boundary.
 */
class EnsureUserCanWrite
{
    /**
     * The methods that cannot change server state.
     *
     * @var list<string>
     */
    private const READ_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), self::READ_METHODS, true)) {
            return $next($request);
        }

        if ($request->user()?->is_read_only) {
            abort(403, __('This account has read-only access.'));
        }

        return $next($request);
    }
}
