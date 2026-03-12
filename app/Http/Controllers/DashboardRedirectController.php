<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('Teacher') && (bool) $user->must_change_password) {
            return redirect()
                ->route('profile.edit')
                ->with('force_password_change', 'You must change your password before continuing.');
        }

        return match (true) {
            $user->hasRole('Admin') => redirect()->route('admin.dashboard'),
            $user->hasRole('Principal') => redirect()->route('principal.dashboard'),
            $user->hasRole('Accountant') => redirect()->route('accountant.dashboard'),
            $user->hasRole('Teacher') => redirect()->route('teacher.dashboard'),
            $user->hasRole('Doctor') => redirect()->route('doctor.dashboard'),
            $user->hasRole('Student') => redirect()->route('student.dashboard'),
            default => redirect()->route('profile.edit'),
        };
    }
}
