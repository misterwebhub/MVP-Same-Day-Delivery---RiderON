<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtpVerification extends Model
{
    use HasFactory;

    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_PICKUP = 'pickup';

    public const PURPOSE_DELIVERY = 'delivery';

    protected $fillable = [
        'order_id',
        'user_id',
        'purpose',
        'phone',
        'otp_hash',
        'expires_at',
        'verified_at',
        'attempt_count',
        'max_attempts',
        'locked_until',
        'resend_count',
        'last_sent_at',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempt_count' => 'integer',
            'max_attempts' => 'integer',
            'locked_until' => 'datetime',
            'resend_count' => 'integer',
            'last_sent_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OtpVerificationLog::class);
    }
}
