<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerNotificationEvent extends Model
{
    protected $fillable = ['user_id', 'event_key', 'sent_at'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
