<?php

namespace App\Services;

use App\Mail\WarrantyCodeMail;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WarrantyCode;
use App\Models\WarrantyCodeSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class WarrantyCodeService
{
    public function generate(int $count): array
    {
        $codes = [];
        $validityMonths = $this->validityMonths();

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

    public function add(string $value): WarrantyCode
    {
        return WarrantyCode::create([
            'code' => $value,
            'validity_months' => $this->validityMonths(),
            'is_active' => false,
        ]);
    }

    public function import(array $values): int
    {
        $existing = collect($values)
            ->chunk(500)
            ->flatMap(fn ($chunk) => WarrantyCode::withTrashed()->whereIn('code', $chunk)->pluck('code'))
            ->values()
            ->all();
        if ($existing !== []) {
            throw ValidationException::withMessages([
                'warranty_sheet' => 'These warranty codes already exist: '.implode(', ', array_slice($existing, 0, 10)).(count($existing) > 10 ? '…' : ''),
            ]);
        }

        $validityMonths = $this->validityMonths();
        DB::transaction(function () use ($values, $validityMonths): void {
            foreach ($values as $value) {
                WarrantyCode::create([
                    'code' => $value,
                    'validity_months' => $validityMonths,
                    'is_active' => false,
                ]);
            }
        });

        return count($values);
    }

    public function validityMonths(): int
    {
        return (int) WarrantyCodeSetting::query()->firstOrCreate(['id' => 1], ['validity_months' => 1])->validity_months;
    }

    public function updateValidityMonths(int $validityMonths): void
    {
        DB::transaction(function () use ($validityMonths): void {
            WarrantyCodeSetting::query()->updateOrCreate(['id' => 1], ['validity_months' => $validityMonths]);

            WarrantyCode::withTrashed()->orderBy('id')->chunkById(200, function ($codes) use ($validityMonths): void {
                foreach ($codes as $code) {
                    $code->validity_months = $validityMonths;
                    if ($code->activated_at) {
                        $code->expires_at = $code->activated_at->copy()->addMonthsNoOverflow($validityMonths);
                    }
                    $code->save();
                }
            });
        });
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
