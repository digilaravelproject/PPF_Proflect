<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyCodeSetting extends Model
{
    protected $fillable = ['validity_months', 'upgrade_window_months', 'qr_access_enabled'];

    protected function casts(): array
    {
        return [
            'validity_months' => 'integer',
            'upgrade_window_months' => 'integer',
            'qr_access_enabled' => 'boolean',
        ];
    }
}
