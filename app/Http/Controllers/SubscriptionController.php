<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
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
        ]);
    }

    public function checkout(Plan $plan): View
    {
        abort_unless($plan->is_active && ! $plan->is_free, 404);

        return view('subscription.checkout', compact('plan'));
    }

    public function activateFree(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($plan->is_active && $plan->is_free && $plan->duration_days, 404);

        DB::transaction(function () use ($request, $plan): void {
            $user = $request->user()->newQuery()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $alreadyClaimed = Subscription::query()
                ->where('user_id', $user->id)
                ->where('plan_id', $plan->id)
                ->exists();

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
                    'metadata' => ['method' => 'free_plan'],
                ]);
                Subscription::create([
                    'user_id' => $user->id,
                    'plan_id' => $plan->id,
                    'payment_id' => $payment->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $plan->subscriptionEndsAt(),
                ]);
            }

            $user->update(['onboarding_completed_at' => now()]);
        });

        return redirect()->route('subscription.success');
    }

    public function skip(Request $request): RedirectResponse
    {
        $request->user()->update(['onboarding_completed_at' => now()]);

        return redirect()->route('dashboard')->with('status', 'You can choose a protection plan whenever you are ready.');
    }
}
