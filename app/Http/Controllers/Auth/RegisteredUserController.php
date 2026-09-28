<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeCustomerMail;
use App\Models\User;
use App\Models\WarrantyCode;
use App\Services\CustomerNotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function checkWarrantyCode(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('warranty_code'));
        if (! preg_match('/^[0-9]{5}$/', $code)) {
            return response()->json(['valid' => false, 'status' => 'invalid', 'message' => 'Enter a 5-digit numeric warranty code.']);
        }

        $warrantyCode = WarrantyCode::withTrashed()->where('code', $code)->first();
        if (! $warrantyCode || $warrantyCode->trashed()) {
            return response()->json(['valid' => false, 'status' => 'invalid', 'message' => 'This warranty code is not valid.']);
        }

        return response()->json($this->availabilityResponse($warrantyCode));
    }

    public function store(Request $request, CustomerNotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['required', 'string', 'max:20'],
            'warranty_code' => ['required', 'digits:5'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()],
            'terms' => ['accepted'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $warrantyCode = WarrantyCode::withTrashed()->where('code', $validated['warranty_code'])->lockForUpdate()->first();
            if (! $warrantyCode || $warrantyCode->trashed() || ! $warrantyCode->isAvailable()) {
                $message = $warrantyCode ? $this->availabilityResponse($warrantyCode)['message'] : 'This warranty code is not valid.';
                throw ValidationException::withMessages(['warranty_code' => $message]);
            }

            $user = User::create(collect($validated)->except(['warranty_code', 'terms'])->all());
            $warrantyCode->update(['used_by_user_id' => $user->id, 'used_at' => now()]);

            return $user;
        });

        event(new Registered($user));
        try {
            Mail::to($user)->send(new WelcomeCustomerMail($user));
        } catch (Throwable $exception) {
            report($exception);
        }
        $notifications->registration($user);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('subscription.index')->with('status', 'Welcome to Proflect. Choose a protection plan or skip for now.');
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
