<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! $request->user()) {
            abort(401);
        }

        if (! $request->user()->hasAnyPermission($permissions)) {
            abort(403, 'You do not have the required permission.');
        }

        return $next($request);
    }
}

