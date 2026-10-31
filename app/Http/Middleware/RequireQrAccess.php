<?php

namespace App\Http\Middleware;

use App\Support\QrAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireQrAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! QrAccess::enabled()
            || $request->routeIs('home', 'login', 'login.legacy', 'login.store', 'login.store.legacy', 'qr-code.show', 'qr-code.legacy', 'qr-access.enter')
            || $request->is('admin', 'admin/*', 'payments/stripe/webhook')
            || $request->user() !== null
            || $request->session()->get((string) config('qr_access.session_key')) === true) {
            return $next($request);
        }

        return redirect()->route('qr-code.show');
    }
}
