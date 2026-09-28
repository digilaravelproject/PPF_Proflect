<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyCode extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'subscription_id', 'is_active', 'validity_months', 'activated_at', 'expires_at', 'used_by_user_id', 'used_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'activated_at' => 'datetime', 'expires_at' => 'datetime', 'used_at' => 'datetime', 'deleted_at' => 'datetime'];
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function claim(): HasOne
    {
        return $this->hasOne(Claim::class);
    }

    public function getStatusAttribute(): string
    {
        if ($this->trashed()) return 'deleted';
        if ($this->used_at) return 'used';
        if (! $this->is_active) return 'inactive';
        if (! $this->expires_at || $this->expires_at->isPast()) return 'expired';

        return 'available';
    }

    public function isAvailable(): bool
    {
        return ! $this->trashed()
            && ! $this->used_at
            && $this->is_active
            && $this->expires_at?->isFuture();
    }
}
