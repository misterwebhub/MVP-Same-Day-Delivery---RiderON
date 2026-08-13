<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only audit trail of every meaningful thing that happens to an
 * order — who did it, from what IP, from what GPS coordinates (when the
 * client provided them), and how far that GPS point was from the relevant
 * station (for arrival-type events). Admin-only visibility (docs fraud-
 * prevention addendum): never exposed to the customer or partner apps —
 * this is dispute-resolution/investigation evidence, not a customer-facing
 * feature. See App\Services\Activity\ActivityLogger for the write side and
 * App\Console\Commands\PurgeStaleActivityLogGeo for retention.
 */
class OrderActivityLog extends Model
{
    public const EVENT_ORDER_BOOKED = 'order_booked';

    public const EVENT_PAYMENT_VERIFIED = 'payment_verified';

    public const EVENT_PARTNER_MATCHED = 'partner_matched';

    public const EVENT_PARTNER_REASSIGNED = 'partner_reassigned';

    public const EVENT_PARTNER_ACCEPTED = 'partner_accepted';

    public const EVENT_RIDER_ARRIVED_PICKUP = 'rider_arrived_pickup';

    public const EVENT_PICKUP_PHOTO_UPLOADED = 'pickup_photo_uploaded';

    public const EVENT_PARCEL_PHOTO_UPLOADED = 'parcel_photo_uploaded';

    public const EVENT_PICKUP_OTP_VERIFIED = 'pickup_otp_verified';

    public const EVENT_PICKUP_OTP_REGENERATED = 'pickup_otp_regenerated';

    public const EVENT_RIDER_ARRIVED_DESTINATION = 'rider_arrived_destination';

    public const EVENT_DELIVERY_PHOTO_UPLOADED = 'delivery_photo_uploaded';

    public const EVENT_DELIVERY_OTP_VERIFIED = 'delivery_otp_verified';

    public const EVENT_DELIVERY_OTP_REGENERATED = 'delivery_otp_regenerated';

    public const EVENT_ORDER_CANCELLED = 'order_cancelled';

    public const ACTOR_CUSTOMER = 'customer';

    public const ACTOR_PARTNER = 'partner';

    public const ACTOR_ADMIN = 'admin';

    public const ACTOR_SYSTEM = 'system';

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'event',
        'actor_type',
        'actor_id',
        'ip_address',
        'user_agent',
        'latitude',
        'longitude',
        'distance_from_target_meters',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
