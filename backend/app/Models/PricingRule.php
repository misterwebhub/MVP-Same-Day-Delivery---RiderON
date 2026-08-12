<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory;

    public const TYPE_BASE = 'base';

    public const TYPE_WEIGHT_SLAB = 'weight_slab';

    public const TYPE_PEAK_HOUR = 'peak_hour';

    public const TYPE_PLATFORM_FEE = 'platform_fee';

    public const TYPE_TAX = 'tax';

    protected $fillable = [
        'route_id',
        'rule_type',
        'min_weight_grams',
        'max_weight_grams',
        'amount_paise',
        'percentage',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_weight_grams' => 'integer',
            'max_weight_grams' => 'integer',
            'amount_paise' => 'integer',
            'percentage' => 'decimal:2',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
