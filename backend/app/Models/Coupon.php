<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public const TYPE_FLAT = 'flat';

    public const TYPE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'code',
        'discount_type',
        'value',
        'max_discount_paise',
        'min_order_amount_paise',
        'usage_limit_total',
        'usage_limit_per_user',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount_paise' => 'integer',
            'min_order_amount_paise' => 'integer',
            'usage_limit_total' => 'integer',
            'usage_limit_per_user' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}
