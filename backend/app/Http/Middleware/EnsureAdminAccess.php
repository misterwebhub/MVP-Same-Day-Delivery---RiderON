<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for every /admin route. Replaces Filament's panel-level
 * FilamentUser::canAccessPanel() check 1:1 — same status+role rule, now
 * exposed as User::canAccessAdmin(). Unauthenticated users are redirected
 * to the admin login form; authenticated-but-ineligible users get a 403.
 */
class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web');

        if ($user === null) {
            return redirect()->guest(route('admin.login'));
        }

        abort_unless($user->canAccessAdmin(), 403, 'You do not have access to the admin panel.');

        return $next($request);
    }
}
