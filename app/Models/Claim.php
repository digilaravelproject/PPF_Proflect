<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Claim extends Model
{
    public const PANELS = [
        'front_bumper' => 'Front Bumper',
        'bonnet' => 'Bonnet',
        'roof' => 'Roof',
        'left_fender' => 'Left Fender',
        'right_fender' => 'Right Fender',
        'left_door' => 'Left Door',
        'right_door' => 'Right Door',
        'rear_bumper' => 'Rear Bumper',
        'other' => 'Other',
    ];

    protected $fillable = ['claim_number', 'user_id', 'subscription_id', 'warranty_code_id', 'vehicle_make', 'vehicle_model', 'vehicle_model_id', 'vehicle_model_photo_path', 'registration_number', 'vehicle_year', 'panels', 'panel_details', 'photos', 'description', 'status', 'admin_notes', 'reviewed_at', 'booking_date'];

    protected function casts(): array
    {
        return ['panels' => 'array', 'panel_details' => 'array', 'photos' => 'array', 'reviewed_at' => 'datetime', 'booking_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function warrantyCode(): BelongsTo
    {
        return $this->belongsTo(WarrantyCode::class)->withTrashed();
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }
}
