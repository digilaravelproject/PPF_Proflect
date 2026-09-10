<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', 'currency', 'duration_years', 'coverage_sqm', 'features', 'accent', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['features' => 'array', 'is_active' => 'boolean', 'coverage_sqm' => 'decimal:2'];
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₹'.number_format($this->price / 100);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
