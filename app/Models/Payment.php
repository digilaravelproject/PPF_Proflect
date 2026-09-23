<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['user_id', 'plan_id', 'gateway', 'gateway_order_id', 'gateway_payment_id', 'amount', 'currency', 'status', 'paid_at', 'metadata'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'metadata' => 'array'];
    }

    public function getFormattedAmountAttribute(): string
    {
        return $this->amount ? $this->currency.' $'.number_format($this->amount / 100, 2) : 'Free';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
