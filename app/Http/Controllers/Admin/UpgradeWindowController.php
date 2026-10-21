<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyCodeSetting;
use App\Services\UpgradeEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UpgradeWindowController extends Controller
{
    public function edit(UpgradeEligibilityService $eligibility): View
    {
        return view('admin.masters.upgrade-window', ['upgradeWindowMonths' => $eligibility->windowMonths()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate(['upgrade_window_months' => ['required', 'integer', 'between:1,12']]);
        WarrantyCodeSetting::query()->updateOrCreate(['id' => 1], [
            'upgrade_window_months' => (int) $validated['upgrade_window_months'],
        ]);

        return back()->with('status', 'Upgrade window updated to '.$validated['upgrade_window_months'].' '.str('month')->plural((int) $validated['upgrade_window_months']).'.');
    }
}
