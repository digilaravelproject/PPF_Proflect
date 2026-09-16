<?php

namespace App\Services;

use App\Models\WarrantyCode;
use Illuminate\Support\Str;

class WarrantyCodeService
{
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
