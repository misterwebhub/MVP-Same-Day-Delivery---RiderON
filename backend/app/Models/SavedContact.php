<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedContact extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_SENDER = 'sender';

    public const TYPE_RECEIVER = 'receiver';

    protected $fillable = [
        'customer_id',
        'type',
        'label',
        'name',
        'phone',
        'station_id',
        'landmark',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }
}
