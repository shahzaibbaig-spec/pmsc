<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChangeMiddleware
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_ROUTE_NAMES = [
        'profile.edit',
        'profile.update',
        'password.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $isTeacher = $user->hasRole('Teacher');
        $requiresPasswordChange = (bool) $user->must_change_password;
        $routeName = $request->route()?->getName();

        if (
            $isTeacher &&
            $requiresPasswordChange &&
            ! in_array($routeName, self::ALLOWED_ROUTE_NAMES, true)
        ) {
            /** @var RedirectResponse $redirect */
            $redirect = redirect()
                ->route('profile.edit')
                ->with('force_password_change', 'You must change your password before continuing.');

            return $redirect;
        }

        return $next($request);
    }
}

