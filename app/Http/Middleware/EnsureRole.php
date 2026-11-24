<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('auth.index')->with('error', 'Please sign in to access that workspace.');
        }

        if (! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('auth.index')->with('error', 'This account is currently deactivated.');
        }

        if (! empty($roles) && ! $user->hasRole($roles)) {
            abort(403, 'Your role does not have permission to access this portal section.');
        }

        return $next($request);
    }
}
