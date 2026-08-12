<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'prohibited_items_version_id',
        'accepted_at',
        'ip_address',
        'device_info',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function prohibitedItemsVersion(): BelongsTo
    {
        return $this->belongsTo(ProhibitedItemsVersion::class);
    }
}
