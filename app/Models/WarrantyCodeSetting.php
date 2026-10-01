<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyCodeSetting extends Model
{
    protected $fillable = ['validity_months'];

    protected function casts(): array
    {
        return ['validity_months' => 'integer'];
    }
}
