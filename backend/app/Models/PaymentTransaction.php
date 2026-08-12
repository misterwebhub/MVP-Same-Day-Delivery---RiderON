<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    /** Recorded for the client-initiated checkout callback (POST /payments/{id}/verify), as opposed to a Razorpay webhook event. */
    public const EVENT_CHECKOUT_VERIFY = 'checkout.verify';

    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'provider_payment_id',
        'provider_signature',
        'event_type',
        'raw_payload',
        'signature_verified',
        'processed',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'signature_verified' => 'boolean',
            'processed' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
