<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    public const CATEGORY_PAYMENT = 'payment';

    public const CATEGORY_PICKUP = 'pickup';

    public const CATEGORY_DELIVERY = 'delivery';

    public const CATEGORY_RIDER = 'rider';

    public const CATEGORY_WRONG_PARCEL = 'wrong_parcel';

    public const CATEGORY_DAMAGED_PARCEL = 'damaged_parcel';

    public const CATEGORY_RECEIVER_UNAVAILABLE = 'receiver_unavailable';

    public const CATEGORY_CANCELLATION = 'cancellation';

    public const CATEGORY_REFUND = 'refund';

    public const CATEGORY_OTHER = 'other';

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'order_id',
        'category',
        'description',
        'status',
        'assigned_to',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class, 'ticket_id');
    }
}
