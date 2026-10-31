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
            || $request->routeIs('home', 'qr-code.show', 'qr-code.legacy', 'qr-access.enter')
            || $request->is('admin', 'admin/*', 'payments/stripe/webhook')
            || $request->session()->get((string) config('qr_access.session_key')) === true) {
            return $next($request);
        }

        return redirect()->route('qr-code.show');
    }
}
