<?php

namespace App\Services;

use App\Models\User;
use App\Models\WarrantyCode;
use App\Models\WarrantyCodeSetting;
use Illuminate\Validation\ValidationException;

class UpgradeEligibilityService
{
    public function windowMonths(): int
    {
        return (int) WarrantyCodeSetting::query()->firstOrCreate(
            ['id' => 1],
            ['validity_months' => 1, 'upgrade_window_months' => 2]
        )->upgrade_window_months;
    }

    public function deadline(WarrantyCode $code)
    {
        return $code->activated_at?->copy()->addMonthsNoOverflow($this->windowMonths())->endOfDay();
    }

    public function isEligible(WarrantyCode $code): bool
    {
        $deadline = $this->deadline($code);

        return $deadline !== null && now()->lte($deadline);
    }

    public function validateSelection(User $user, ?int $warrantyCodeId): WarrantyCode
    {
        if (! $warrantyCodeId) {
            throw ValidationException::withMessages(['warranty_code_id' => 'Select a warranty card before upgrading your plan.']);
        }

        $code = $user->usedWarrantyCodes()->whereKey($warrantyCodeId)->first();
        if (! $code) {
            throw ValidationException::withMessages(['warranty_code_id' => 'The selected warranty card does not belong to your account.']);
        }
        if (! $this->isEligible($code)) {
            $deadline = $this->deadline($code);
            throw ValidationException::withMessages(['warranty_code_id' => $deadline
                ? 'The upgrade window for warranty card '.$code->code.' expired on '.$deadline->format('d M Y').'. Please contact Proflect support.'
                : 'This warranty card has no activation date and cannot be used for an upgrade. Please contact Proflect support.']);
        }

        return $code;
    }
}
