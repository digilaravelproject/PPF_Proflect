<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleModel extends Model
{
    protected $appends = ['photo_url'];

    protected $fillable = ['vehicle_make_id', 'name', 'kind', 'catalog_key', 'body_types', 'year_start', 'year_end', 'photo_path', 'coverage_sqm', 'is_active'];

    public function getPhotoUrlAttribute(): ?string { return $this->photo_path ? route('catalog.models.photo', $this) : null; }

    protected function casts(): array { return ['is_active' => 'boolean']; }

    public function make(): BelongsTo { return $this->belongsTo(VehicleMake::class, 'vehicle_make_id'); }
    public function panels(): HasMany { return $this->hasMany(VehicleModelPanel::class); }
}
