<?php

namespace App\Http\Controllers;

use App\Support\QrAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QrAccessController extends Controller
{
    public function show(): View
    {
        return view('qr-code', [
            'entryUrl' => QrAccess::entryUrl(),
        ]);
    }

    public function enter(Request $request, string $token): RedirectResponse
    {
        abort_unless(hash_equals(QrAccess::token(), $token), 404);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        QrAccess::grant($request);

        return redirect()->route('register');
    }
}
