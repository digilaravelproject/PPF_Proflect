<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleModelPanel extends Model
{
    protected $fillable = ['vehicle_model_id', 'key', 'name', 'min_sqm', 'max_sqm', 'photo_x', 'photo_y', 'photo_polygon'];

    protected function casts(): array { return ['photo_polygon' => 'array']; }

    public function model(): BelongsTo { return $this->belongsTo(VehicleModel::class, 'vehicle_model_id'); }
}
