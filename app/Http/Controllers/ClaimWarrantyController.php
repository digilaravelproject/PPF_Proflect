<?php

namespace App\Http\Controllers;

use App\Models\WarrantyCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClaimWarrantyController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->claims()->exists()) {
            return redirect()->route('claims.create');
        }

        return view('claims.warranty-code');
    }

    public function check(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('warranty_code'));
        if (! preg_match('/^[0-9]{5}$/', $code)) {
            return response()->json(['valid' => false, 'status' => 'invalid', 'message' => 'Enter a 5-digit numeric warranty code.']);
        }

        $warrantyCode = WarrantyCode::withTrashed()->where('code', $code)->first();

        return response()->json($warrantyCode
            ? $this->availabilityResponse($warrantyCode)
            : ['valid' => false, 'status' => 'invalid', 'message' => 'This warranty code is not valid.']);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->user()->claims()->exists()) {
            return redirect()->route('claims.create');
        }

        $validated = $request->validate(['warranty_code' => ['required', 'digits:5']]);

        DB::transaction(function () use ($request, $validated): void {
            $warrantyCode = WarrantyCode::withTrashed()
                ->where('code', $validated['warranty_code'])
                ->lockForUpdate()
                ->first();

            if (! $warrantyCode || ! $warrantyCode->isAvailable()) {
                $message = $warrantyCode ? $this->availabilityResponse($warrantyCode)['message'] : 'This warranty code is not valid.';
                throw ValidationException::withMessages(['warranty_code' => $message]);
            }

            $warrantyCode->update([
                'used_by_user_id' => $request->user()->id,
                'used_at' => now(),
            ]);
        });

        $request->session()->put('claim_subscription_flow', true);

        return redirect()->route('subscription.index')->with('status', 'Warranty code verified. Choose a new subscription for your next claim.');
    }

    private function availabilityResponse(WarrantyCode $code): array
    {
        if ($code->trashed()) return ['valid' => false, 'status' => 'deleted', 'message' => 'This warranty code is not valid.'];
        if ($code->used_at) return ['valid' => false, 'status' => 'used', 'message' => 'This warranty code has already been used.'];
        if (! $code->is_active) return ['valid' => false, 'status' => 'inactive', 'message' => 'This warranty code is not active. Please contact Proflect.'];
        if (! $code->expires_at || $code->expires_at->isPast()) return ['valid' => false, 'status' => 'expired', 'message' => 'This warranty code has expired.'];

        return ['valid' => true, 'status' => 'available', 'message' => 'Warranty code verified successfully.'];
    }
}
