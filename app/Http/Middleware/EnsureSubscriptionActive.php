<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $hasSubscriptionHistory = $user->subscriptions()->exists();
        $hasActiveSubscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();

        if ($hasSubscriptionHistory && ! $hasActiveSubscription) {
            return redirect()->route('subscription.index');
        }

        return $next($request);
    }
}
