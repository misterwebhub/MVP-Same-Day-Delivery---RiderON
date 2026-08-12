<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level role gate (e.g. `role:partner`), for endpoints under a single
 * role rather than a Sanctum token-ability check. Mirrors the existing
 * `$user->role === User::ROLE_*` convention already used in AuthController
 * rather than wiring up Sanctum's CheckAbilities/CheckForAnyAbility.
 */
class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(in_array($request->user()?->role, $roles, true), 403);

        return $next($request);
    }
}
