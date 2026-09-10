<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        return $request->user()->onboarding_completed_at
            ? $next($request)
            : redirect()->route('subscription.index');
    }
}
