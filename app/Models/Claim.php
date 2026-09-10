<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    public const PANELS = [
        'front_bumper' => 'Front Bumper',
        'bonnet' => 'Bonnet',
        'left_fender' => 'Left Fender',
        'right_fender' => 'Right Fender',
        'left_door' => 'Left Door',
        'right_door' => 'Right Door',
        'rear_bumper' => 'Rear Bumper',
        'other' => 'Other',
    ];

    protected $fillable = ['claim_number', 'user_id', 'subscription_id', 'vehicle_make', 'vehicle_model', 'registration_number', 'vehicle_year', 'panels', 'photos', 'description', 'status', 'admin_notes', 'reviewed_at'];

    protected function casts(): array
    {
        return ['panels' => 'array', 'photos' => 'array', 'reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
