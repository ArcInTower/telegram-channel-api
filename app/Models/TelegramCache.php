<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TelegramCache extends Model
{
    use HasFactory;

    protected $table = 'telegram_cache';

    protected $fillable = [
        'channel_username',
        'last_message_id',
        'last_checked_at',
        'expires_at',
        'metadata'
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
        'last_message_id' => 'integer'
    ];

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                     ->orWhereNull('expires_at');
    }
}