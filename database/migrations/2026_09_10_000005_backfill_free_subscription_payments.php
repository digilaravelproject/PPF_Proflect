<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscriptions')
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->whereNull('subscriptions.payment_id')
            ->where('plans.price', 0)
            ->select('subscriptions.id', 'subscriptions.user_id', 'subscriptions.plan_id', 'subscriptions.starts_at')
            ->orderBy('subscriptions.id')
            ->each(function ($subscription): void {
                $paymentId = DB::table('payments')->insertGetId([
                    'user_id' => $subscription->user_id,
                    'plan_id' => $subscription->plan_id,
                    'gateway' => 'free',
                    'gateway_order_id' => 'free_subscription_'.$subscription->id,
                    'amount' => 0,
                    'currency' => 'INR',
                    'status' => 'paid',
                    'paid_at' => $subscription->starts_at,
                    'metadata' => json_encode(['method' => 'free_plan']),
                    'created_at' => $subscription->starts_at,
                    'updated_at' => now(),
                ]);
                DB::table('subscriptions')->where('id', $subscription->id)->update(['payment_id' => $paymentId]);
            });
    }

    public function down(): void
    {
        DB::table('subscriptions')->whereIn('payment_id', DB::table('payments')->where('gateway', 'free')->select('id'))->update(['payment_id' => null]);
        DB::table('payments')->where('gateway', 'free')->delete();
    }
};
