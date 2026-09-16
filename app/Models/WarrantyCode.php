<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyCode extends Model
{
    use SoftDeletes;

    protected $fillable = ['code', 'is_active', 'used_by_user_id', 'used_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'used_at' => 'datetime', 'deleted_at' => 'datetime'];
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }

    public function claim(): HasOne
    {
        return $this->hasOne(Claim::class);
    }

    public function getStatusAttribute(): string
    {
        return $this->trashed() ? 'deleted' : ($this->used_at ? 'used' : ($this->is_active ? 'available' : 'inactive'));
    }
}
