<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyCodeSetting;
use App\Support\QrAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    public function edit(): View
    {
        return view('admin.masters.qr-code', [
            'entryUrl' => QrAccess::entryUrl(),
            'qrAccessEnabled' => QrAccess::enabled(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['qr_access_enabled' => ['required', 'boolean']]);
        $enabled = (bool) $validated['qr_access_enabled'];

        WarrantyCodeSetting::query()->updateOrCreate(['id' => 1], [
            'qr_access_enabled' => $enabled,
        ]);

        return back()->with('status', $enabled
            ? 'QR-only customer access enabled.'
            : 'QR-only customer access disabled. Customers can now open the site URL directly.');
    }
}
