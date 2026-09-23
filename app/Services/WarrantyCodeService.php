<?php

namespace App\Services;

use App\Models\WarrantyCode;
use App\Models\Subscription;
use App\Mail\WarrantyCodeMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WarrantyCodeService
{
    public function issueForSubscription(Subscription $subscription): WarrantyCode
    {
        return DB::transaction(function () use ($subscription): WarrantyCode {
            Subscription::query()->whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            $existing = WarrantyCode::query()->where('subscription_id', $subscription->id)
                ->where('is_active', true)->whereNull('used_at')->first();
            if ($existing) return $existing;

            $code = WarrantyCode::query()->whereNull('subscription_id')->where('is_active', true)
                ->whereNull('used_at')->lockForUpdate()->first() ?? $this->generate()[0];
            $code->update(['subscription_id' => $subscription->id]);

            return $code;
        });
    }

    public function emailForSubscription(Subscription $subscription): WarrantyCode
    {
        $code = $this->issueForSubscription($subscription);
        Mail::to($subscription->user)->send(new WarrantyCodeMail($subscription->loadMissing('plan'), $code));

        return $code;
    }

    public function generate(int $count = 1): array
    {
        $codes = [];
        for ($index = 0; $index < $count; $index++) {
            do {
                $code = 'CLM-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            } while (WarrantyCode::withTrashed()->where('code', $code)->exists());

            $codes[] = WarrantyCode::create(['code' => $code, 'is_active' => true]);
        }

        return $codes;
    }
}
