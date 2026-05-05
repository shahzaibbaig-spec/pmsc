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

        $normalizedPermissions = collect($permissions)
            ->flatMap(static fn (string $permission): array => preg_split('/[|,]/', $permission) ?: [])
            ->map(static fn (string $permission): string => trim($permission))
            ->filter(static fn (string $permission): bool => $permission !== '')
            ->values()
            ->all();

        if (! $request->user()->hasAnyPermission($normalizedPermissions)) {
            abort(403, 'You do not have the required permission.');
        }

        return $next($request);
    }
}
