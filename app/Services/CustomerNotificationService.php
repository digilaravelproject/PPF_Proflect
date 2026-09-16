<?php

namespace App\Services;

use App\Models\Claim;
use App\Models\CustomerNotificationEvent;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\CustomerEventNotification;
use Illuminate\Database\QueryException;
use Throwable;

class CustomerNotificationService
{
    public function send(User $user, string $eventKey, string $event, string $title, string $message, ?string $url = null, ?string $action = null, array $links = [], bool $sendMail = true): bool
    {
        try {
            CustomerNotificationEvent::create(['user_id' => $user->id, 'event_key' => $eventKey, 'sent_at' => now()]);
        } catch (QueryException) {
            return false;
        }

        $notification = new CustomerEventNotification($event, $title, $message, $url, $action, $links, $sendMail);
        try {
            $user->notify($notification);
        } catch (Throwable $exception) {
            report($exception);
            CustomerNotificationEvent::query()->where('user_id', $user->id)->where('event_key', $eventKey)->delete();
            $user->notifications()->whereKey($notification->id)->delete();

            return false;
        }

        return true;
    }

    public function registration(User $user): void
    {
        $days = config('proflect.offer_duration_days', 30);
        $this->send($user, 'registration', 'registration', 'Warranty registered — your replacement offer is ready', "Welcome to Proflect. Your warranty is registered and your PPF Replacement Program offer is available for {$days} days.", route('subscription.index'), 'View your offer', [], false);
    }

    public function paymentSuccessful(Payment $payment, Subscription $subscription): bool
    {
        return $this->send(
            $payment->user,
            'payment_success:'.$payment->id,
            'payment_success',
            'Program activated — payment successful',
            "Your {$payment->plan->name} is active. Your tax invoice and warranty certificate are ready to download.",
            route('documents.index'),
            'View documents',
            [
                ['label' => 'Download tax invoice', 'url' => route('documents.invoice', $payment)],
                ['label' => 'Download warranty certificate', 'url' => route('documents.certificate', $subscription)],
            ], false,
        );
    }

    public function claimReceived(Claim $claim): void
    {
        $this->send($claim->user, 'claim_received:'.$claim->id, 'claim_received', 'We received your panel replacement request', "Claim {$claim->claim_number} has been received and is now awaiting review.", route('claims.show', $claim), 'View claim');
    }

    public function claimDecision(Claim $claim): void
    {
        if ($claim->status === 'approved') {
            $date = $claim->booking_date?->format('d M Y');
            $this->send($claim->user, 'claim_approved:'.$claim->id, 'claim_approved', 'Your panel replacement request is approved', "Claim {$claim->claim_number} is approved. Your booking date is {$date}.", route('claims.show', $claim), 'View booking');
        } else {
            $this->send($claim->user, 'claim_declined:'.$claim->id, 'claim_declined', 'Your panel replacement request could not be approved', "Claim {$claim->claim_number} was declined. Reason: {$claim->admin_notes}", route('claims.show', $claim), 'View decision');
        }
    }
}
