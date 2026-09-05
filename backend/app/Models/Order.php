<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const CANCELLED_BY_CUSTOMER = 'customer';

    public const CANCELLED_BY_PARTNER = 'partner';

    public const CANCELLED_BY_ADMIN = 'admin';

    public const CANCELLED_BY_SYSTEM = 'system';

    protected $fillable = [
        'booking_reference',
        'customer_id',
        'route_id',
        'route_schedule_id',
        'partner_id',
        'status',
        'booking_date',
        'sender_name',
        'sender_phone',
        'sender_landmark',
        'receiver_name',
        'receiver_phone',
        'receiver_landmark',
        'pickup_address_text',
        'pickup_latitude',
        'pickup_longitude',
        'delivery_address_text',
        'delivery_latitude',
        'delivery_longitude',
        'price_breakdown',
        'total_amount_paise',
        'currency',
        'prohibited_items_declared_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'arrived_destination_at',
        'waiting_deadline_at',
        'pickup_proof_photo_path',
        'delivery_proof_photo_path',
        'delivered_at',
        'completed_at',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
            'delivery_latitude' => 'decimal:7',
            'delivery_longitude' => 'decimal:7',
            'price_breakdown' => 'array',
            'total_amount_paise' => 'integer',
            'prohibited_items_declared_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'arrived_destination_at' => 'datetime',
            'waiting_deadline_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function routeSchedule(): BelongsTo
    {
        return $this->belongsTo(RouteSchedule::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'partner_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function declaration(): HasOne
    {
        return $this->hasOne(OrderDeclaration::class);
    }

    public function parcel(): HasOne
    {
        return $this->hasOne(Parcel::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function otpVerifications(): HasMany
    {
        return $this->hasMany(OtpVerification::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OrderActivityLog::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
