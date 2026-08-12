<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpVerificationLog extends Model
{
    use HasFactory;

    public const ATTEMPTED_BY_CUSTOMER = 'customer';

    public const ATTEMPTED_BY_PARTNER = 'partner';

    public const RESULT_SUCCESS = 'success';

    public const RESULT_INVALID = 'invalid';

    public const RESULT_EXPIRED = 'expired';

    public const RESULT_LOCKED = 'locked';

    public $timestamps = false;

    protected $fillable = [
        'otp_verification_id',
        'attempted_by_type',
        'attempted_by_id',
        'result',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function otpVerification(): BelongsTo
    {
        return $this->belongsTo(OtpVerification::class);
    }
}
