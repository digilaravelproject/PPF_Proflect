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
            'status' => ['nullable', Rule::in(['available', 'used', 'inactive', 'deleted'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $codes = WarrantyCode::withTrashed()->with(['usedBy', 'claim'])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim($request->string('search')->toString());
                $query->where(fn (Builder $query) => $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('usedBy', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
            })
            ->when($request->string('status')->toString() === 'available', fn (Builder $query) => $query->whereNull('deleted_at')->whereNull('used_at')->where('is_active', true))
            ->when($request->string('status')->toString() === 'used', fn (Builder $query) => $query->whereNotNull('used_at')->whereNull('deleted_at'))
            ->when($request->string('status')->toString() === 'inactive', fn (Builder $query) => $query->whereNull('used_at')->where('is_active', false)->whereNull('deleted_at'))
            ->when($request->string('status')->toString() === 'deleted', fn (Builder $query) => $query->onlyTrashed())
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.warranty-codes.index', compact('codes'));
    }

    public function store(Request $request, WarrantyCodeService $generator): RedirectResponse
    {
        $validated = $request->validate(['count' => ['required', 'integer', 'min:1', 'max:100']]);
        $generator->generate((int) $validated['count']);

        return back()->with('status', $validated['count'].' new warranty '.str('code')->plural((int) $validated['count']).' generated.');
    }

    public function show(int $warrantyCode): View
    {
        $code = WarrantyCode::withTrashed()->with(['usedBy', 'claim.subscription.plan'])->findOrFail($warrantyCode);

        return view('admin.warranty-codes.show', ['code' => $code]);
    }

    public function toggle(int $warrantyCode, WarrantyCodeService $generator): RedirectResponse
    {
        $code = WarrantyCode::query()->findOrFail($warrantyCode);
        $wasActive = $code->is_active;
        $code->update(['is_active' => ! $wasActive]);
        if ($wasActive) {
            $generator->generate();
        }

        return back()->with('status', "Warranty code {$code->code} is now ".($code->is_active ? 'active.' : 'inactive.'));
    }

    public function destroy(int $warrantyCode, WarrantyCodeService $generator): RedirectResponse
    {
        $code = WarrantyCode::query()->findOrFail($warrantyCode);
        $value = $code->code;
        $code->delete();
        $generator->generate();

        return redirect()->route('admin.warranty-codes.index')->with('status', "Warranty code {$value} deleted and a replacement generated.");
    }
}
