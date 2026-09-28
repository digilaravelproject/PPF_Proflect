<?php

namespace App\Services;

use App\Models\WarrantyCode;
use App\Mail\WarrantyCodeMail;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class WarrantyCodeService
{
    public function generate(int $count, int $validityMonths): array
    {
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            do {
                $value = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            } while (WarrantyCode::withTrashed()->where('code', $value)->exists());

            $codes[] = WarrantyCode::create([
                'code' => $value,
                'validity_months' => $validityMonths,
                'is_active' => false,
            ]);
        }

        return $codes;
    }

    public function forUser(User $user): ?WarrantyCode
    {
        return WarrantyCode::query()->where('used_by_user_id', $user->id)->latest('used_at')->first();
    }

    public function emailForSubscription(Subscription $subscription): WarrantyCode
    {
        $code = $this->forUser($subscription->user);
        abort_unless($code, 404);
        Mail::to($subscription->user)->send(new WarrantyCodeMail($subscription->loadMissing('plan'), $code));

        return $code;
    }
}
