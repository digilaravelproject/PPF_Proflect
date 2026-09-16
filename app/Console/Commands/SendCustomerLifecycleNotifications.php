<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\User;
use App\Services\CustomerNotificationService;
use Illuminate\Console\Command;

class SendCustomerLifecycleNotifications extends Command
{
    protected $signature = 'proflect:send-lifecycle-notifications';

    protected $description = 'Send due customer offer and subscription expiry notifications';

    public function handle(CustomerNotificationService $notifications): int
    {
        $offerDays = config('proflect.offer_duration_days', 30);
        $day14 = config('proflect.offer_reminder_day', 14);

        User::query()->whereDoesntHave('subscriptions')->where('created_at', '<=', now()->subDays($day14))->chunkById(100, function ($users) use ($notifications, $offerDays): void {
            foreach ($users as $user) {
                $expires = $user->created_at->copy()->addDays($offerDays);
                if ($expires->isFuture()) {
                    $notifications->send($user, 'offer_day_14', 'offer_day_14', 'Your Proflect replacement offer is still available', 'Your PPF Replacement Program offer is waiting. Activate it before '.$expires->format('d M Y').'.', route('subscription.index'), 'View offer');
                }
            }
        });

        User::query()->whereDoesntHave('subscriptions')->whereBetween('created_at', [now()->subDays($offerDays), now()->subDays($offerDays)->addHours(48)])->chunkById(100, function ($users) use ($notifications): void {
            foreach ($users as $user) {
                $notifications->send($user, 'offer_48_hours', 'offer_48_hours', 'Your PPF Replacement Program offer expires in 48 hours', 'Only 48 hours remain to activate your Proflect replacement offer.', route('subscription.index'), 'Activate offer');
            }
        });

        $noticeDays = config('proflect.term_notice_days', 30);
        Subscription::query()->with(['user', 'plan'])->where('status', 'active')->where('ends_at', '>', now())->where('ends_at', '<=', now()->addDays($noticeDays))->chunkById(100, function ($subscriptions) use ($notifications): void {
            foreach ($subscriptions as $subscription) {
                $notifications->send($subscription->user, 'term_expiry:'.$subscription->id, 'term_expiry', 'Your PPF Replacement Program is approaching expiry', "Your {$subscription->plan->name} expires on {$subscription->ends_at->format('d M Y')}. Renew to keep your coverage active.", route('subscription.index'), 'View protection plans');
            }
        });

        $this->info('Customer lifecycle notifications processed.');

        return self::SUCCESS;
    }
}
