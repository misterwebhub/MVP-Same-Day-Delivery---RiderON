<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    use HasFactory;

    public const PLATFORM_ANDROID = 'android';

    public const PLATFORM_IOS = 'ios';

    protected $fillable = [
        'user_id',
        'fcm_token',
        'platform',
        'last_seen_at',
    ];

    protected $hidden = [
        'fcm_token',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
