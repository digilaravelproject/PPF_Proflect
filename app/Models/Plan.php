<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', 'currency', 'duration_years', 'duration_days', 'coverage_sqm', 'features', 'accent', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'duration_years' => 'integer', 'duration_days' => 'integer', 'features' => 'array', 'is_active' => 'boolean', 'coverage_sqm' => 'decimal:2'];
    }

    public function getFormattedPriceAttribute(): string
    {
        return $this->is_free ? 'Free' : '$'.number_format($this->price / 100, 2);
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->price === 0;
    }

    public function getDurationLabelAttribute(): string
    {
        return $this->duration_days
            ? $this->duration_days.' '.str('day')->plural($this->duration_days)
            : $this->duration_years.' '.str('year')->plural($this->duration_years);
    }

    public function subscriptionEndsAt()
    {
        return $this->duration_days
            ? now()->addDays($this->duration_days)
            : now()->addYears($this->duration_years);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
