<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyCode;
use App\Services\WarrantyCodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WarrantyCodeController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['available', 'used', 'expired', 'inactive', 'deleted'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $codes = WarrantyCode::withTrashed()->with(['usedBy', 'claim', 'subscription.user'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(fn (Builder $query) => $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('usedBy', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('subscription.user', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->string('status')->toString() === 'available', fn (Builder $query) => $query->whereNull('deleted_at')->whereNull('used_at')->where('is_active', true)->where('expires_at', '>', now()))
            ->when($request->string('status')->toString() === 'used', fn (Builder $query) => $query->whereNotNull('used_at')->whereNull('deleted_at'))
            ->when($request->string('status')->toString() === 'inactive', fn (Builder $query) => $query->whereNull('used_at')->where('is_active', false)->whereNull('deleted_at'))
            ->when($request->string('status')->toString() === 'expired', fn (Builder $query) => $query->whereNull('used_at')->where('is_active', true)->where('expires_at', '<=', now())->whereNull('deleted_at'))
            ->when($request->string('status')->toString() === 'deleted', fn (Builder $query) => $query->onlyTrashed())
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.warranty-codes.index', compact('codes'));
    }

    public function store(Request $request, WarrantyCodeService $codes): RedirectResponse
    {
        $validated = $request->validate([
            'count' => ['required', 'integer', 'between:1,100'],
            'validity_months' => ['required', 'integer', 'between:1,12'],
        ]);
        $codes->generate((int) $validated['count'], (int) $validated['validity_months']);

        return back()->with('status', $validated['count'].' new five-digit warranty '.str('code')->plural((int) $validated['count']).' generated. Activate '.((int) $validated['count'] === 1 ? 'it' : 'them').' when ready.');
    }

    public function storeManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'manual_code' => ['required', 'digits:5', Rule::unique('warranty_codes', 'code')],
            'manual_validity_months' => ['required', 'integer', 'between:1,12'],
        ]);
        WarrantyCode::create([
            'code' => $validated['manual_code'],
            'validity_months' => $validated['manual_validity_months'],
            'is_active' => false,
        ]);

        return back()->with('status', "Warranty code {$validated['manual_code']} added. Activate it when it is ready for use.");
    }

    public function show(int $warrantyCode): View
    {
        $code = WarrantyCode::withTrashed()->with(['usedBy', 'subscription.user', 'subscription.plan', 'claim.subscription.plan'])->findOrFail($warrantyCode);

        return view('admin.warranty-codes.show', ['code' => $code]);
    }

    public function toggle(int $warrantyCode): RedirectResponse
    {
        $code = WarrantyCode::query()->findOrFail($warrantyCode);
        if ($code->used_at) return back()->withErrors(['code' => 'A used warranty code cannot be reactivated.']);

        $code->update($code->is_active
            ? ['is_active' => false]
            : ['is_active' => true, 'activated_at' => now(), 'expires_at' => now()->addMonthsNoOverflow($code->validity_months)]);

        return back()->with('status', "Warranty code {$code->code} is now ".($code->is_active ? 'active.' : 'inactive.'));
    }

    public function destroy(int $warrantyCode): RedirectResponse
    {
        $code = WarrantyCode::query()->findOrFail($warrantyCode);
        $value = $code->code;
        $code->delete();

        return redirect()->route('admin.warranty-codes.index')->with('status', "Warranty code {$value} deleted.");
    }
}
