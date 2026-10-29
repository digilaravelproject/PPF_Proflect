<?php

namespace App\Http\Controllers;

use App\Mail\PaymentSuccessfulMail;
use App\Models\Claim;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\WarrantyCode;
use App\Services\CustomerNotificationService;
use App\Services\PendingClaimService;
use App\Services\UpgradeEligibilityService;
use App\Services\WarrantyCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SubscriptionController extends Controller
{
    public function index(Request $request, UpgradeEligibilityService $eligibility): View
    {
        $currentSubscription = $request->user()->subscriptions()
            ->with('plan')->where('status', 'active')->where('ends_at', '>', now())->latest()->first();
        $warrantyCards = $request->user()->usedWarrantyCodes()->with(['subscription.plan', 'claim'])->latest('used_at')->get();
        $warrantyCards->each(function (WarrantyCode $code) use ($eligibility): void {
            $code->setAttribute('upgrade_deadline', $eligibility->deadline($code));
            $code->setAttribute('upgrade_eligible', $eligibility->isEligible($code));
        });
        $selectedWarrantyCard = $warrantyCards->firstWhere('id', (int) ($request->input('warranty_code_id') ?: $request->session()->get('subscribing_warranty_code_id')))
            ?? $warrantyCards->first(fn (WarrantyCode $card) => ! $card->subscription_id && ! $card->claim)
            ?? $warrantyCards->first();
        if ($selectedWarrantyCard) $request->session()->put('subscribing_warranty_code_id', $selectedWarrantyCard->id);

        $expiredSubscription = $request->user()->subscriptions()
            ->with('plan')
            ->where('ends_at', '<=', now())
            ->latest('ends_at')
            ->first();

        return view('subscription.index', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'expiredSubscription' => $expiredSubscription,
            'usedFreePlanIds' => $request->user()->subscriptions()
                ->whereHas('plan', fn ($query) => $query->where('price', 0))
                ->pluck('plan_id'),
            'currentSubscription' => $currentSubscription,
            'warrantyCards' => $warrantyCards,
            'upgradeWindowMonths' => $eligibility->windowMonths(),
            'selectedWarrantyCard' => $selectedWarrantyCard,
        ]);
    }

    public function checkout(Request $request, Plan $plan, UpgradeEligibilityService $eligibility): View|RedirectResponse
    {
        abort_unless($plan->is_active && ! $plan->is_free, 404);
        $currentSubscription = $request->user()->subscriptions()
            ->with('plan')->where('status', 'active')->where('ends_at', '>', now())->latest()->first();
        $warrantyCards = $request->user()->usedWarrantyCodes()->with(['subscription.plan', 'claim'])->latest('used_at')->get();
        $warrantyCards->each(function (WarrantyCode $code) use ($eligibility): void {
            $code->setAttribute('upgrade_deadline', $eligibility->deadline($code));
            $code->setAttribute('upgrade_eligible', $eligibility->isEligible($code));
        });
        $selectedCardId = $request->input('warranty_code_id') ?: $request->session()->get('subscribing_warranty_code_id') ?: $warrantyCards->first()?->id;
        $selectedWarrantyCard = $eligibility->validateSelection($request->user(), $selectedCardId ? (int) $selectedCardId : null);
        if ($selectedWarrantyCard->subscription?->plan_id === $plan->id) {
            return redirect()->route('subscription.index', ['warranty_code_id' => $selectedWarrantyCard->id])
                ->withErrors(['plan' => 'Warranty card '.$selectedWarrantyCard->code.' already has '.$plan->name.'.']);
        }
        $duplicate = $request->user()->usedWarrantyCodes()->with('subscription.plan')
            ->where('id', '!=', $selectedWarrantyCard->id)
            ->whereHas('subscription', fn ($query) => $query->where('plan_id', $plan->id))
            ->first();
        if ($duplicate) {
            return redirect()->route('subscription.index', ['warranty_code_id' => $selectedWarrantyCard->id])
                ->withErrors(['plan' => 'You already have '.$plan->name.' on warranty card '.$duplicate->code.'.']);
        }

        return view('subscription.checkout', compact('plan', 'currentSubscription', 'warrantyCards', 'selectedWarrantyCard'));
    }

    public function activateFree(Request $request, Plan $plan, CustomerNotificationService $notifications, WarrantyCodeService $codes, PendingClaimService $pendingClaims): RedirectResponse
    {
        abort_unless($plan->is_active && $plan->is_free && $plan->duration_days, 404);
        $validated = $request->validate(['warranty_code_id' => ['nullable', 'integer']]);
        $selectedCodeId = $validated['warranty_code_id'] ?? $request->session()->get('subscribing_warranty_code_id')
            ?? $request->user()->usedWarrantyCodes()->whereDoesntHave('claim')->value('id');
        $selectedCode = $request->user()->usedWarrantyCodes()->with('claim')->whereKey($selectedCodeId)->first();
        if (! $selectedCode || ! $selectedCode->isAvailableForClaim()) {
            return back()->withErrors(['warranty_code_id' => 'Select an available warranty card.']);
        }

        $pendingClaimId = $request->session()->get('pending_claim_id');
        $subscription = DB::transaction(function () use ($request, $plan, $pendingClaimId, $selectedCode): Subscription {
            $user = $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $alreadyClaimed = Subscription::query()
                ->where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->exists();

            $duplicateCode = WarrantyCode::query()->where('used_by_user_id', $user->id)->where('id', '!=', $selectedCode->id)
                ->whereHas('subscription', fn ($query) => $query->where('plan_id', $plan->id))->first();
            if ($duplicateCode) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'plan' => 'You already have '.$plan->name.' on warranty card '.$duplicateCode->code.'.',
                ]);
            }

            if (! $alreadyClaimed) {
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'gateway' => 'free',
                    'gateway_order_id' => 'free_'.str()->uuid(),
                    'amount' => 0,
                    'currency' => $plan->currency,
                    'status' => 'paid',
                    'paid_at' => now(),
                    'metadata' => ['method' => 'free_plan', 'pending_claim_id' => $pendingClaimId],
                ]);
                $subscription = Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_id' => $payment->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $plan->subscriptionEndsAt(),
                ]);
            } else {
                $subscription = Subscription::query()->where('user_id', $user->id)->where('plan_id', $plan->id)->firstOrFail();
            }

            $user->update(['onboarding_completed_at' => now()]);
            WarrantyCode::query()->whereKey($selectedCode->id)->where('used_by_user_id', $user->id)
                ->update(['subscription_id' => $subscription->id]);

            return $subscription;
        });

        $payment = $subscription->payment()->with(['plan', 'user'])->firstOrFail();
        $code = null;
        try {
            $code = $codes->forUser($request->user());
        } catch (Throwable $exception) {
            report($exception);
        }
        if ($notifications->paymentSuccessful($payment, $subscription)) {
            if ($code) {
                try {
                    Mail::to($request->user())->send(new PaymentSuccessfulMail($payment, $code));
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }

        $pendingClaims->finalizeFor($subscription, $pendingClaimId ? (int) $pendingClaimId : null);
        $request->session()->forget('subscribing_warranty_code_id');

        return redirect()->route('subscription.success');
    }

    public function skip(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return redirect()->route('dashboard')->with('status', 'You can choose a protection plan whenever you are ready.');
    }
}
