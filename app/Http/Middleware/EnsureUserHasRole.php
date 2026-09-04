<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more of the five roles.
 *
 * Registered as the 'role' alias in bootstrap/app.php, so route files read:
 *
 *     Route::middleware(['auth', 'role:hotel_staff'])->group(...);
 *     Route::middleware(['auth', 'role:admin,park_staff'])->group(...);
 *
 * Several roles are comma-separated and mean "any of these", which is why the
 * parameter is variadic. Pair it with 'auth': that redirects a guest to the
 * login page, and this one decides what a logged-in user is allowed to reach.
 *
 * See BUILD_CONTRACT.md section 6, seam 2. The role names it matches are the
 * seeded roles.name values, not labels - visitor, hotel_staff, ferry_operator,
 * park_staff, admin.
 */
class EnsureUserHasRole
{
    /**
     * @param  Closure(Request): Response  $next
     * @param  string  ...$roles  One or more seeded roles.name values.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Not logged in at all. Normally 'auth' has already redirected, so
        // reaching here means this route is missing it - fail closed rather
        // than quietly letting the request through.
        if ($user === null) {
            abort(401);
        }

        // 'role' with no arguments names nothing and can never be satisfied.
        // Treat it as the mistake it is instead of allowing everyone.
        if ($roles === []) {
            abort(403, 'This route uses the role middleware without naming a role.');
        }

        if (! $user->hasAnyRole($roles)) {
            abort(403, 'Your account does not have access to this area.');
        }

        return $next($request);
    }
}
