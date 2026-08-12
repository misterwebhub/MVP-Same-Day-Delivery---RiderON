<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parcel extends Model
{
    use HasFactory;

    public const TYPE_DOCUMENTS = 'documents';

    public const TYPE_CLOTHING = 'clothing';

    public const TYPE_ELECTRONICS = 'electronics';

    public const TYPE_GIFTS = 'gifts';

    public const TYPE_BOOKS = 'books';

    public const TYPE_OTHER = 'other';

    public const WEIGHT_UPTO_1KG = 'upto_1kg';

    public const WEIGHT_1_3KG = '1_3kg';

    public const WEIGHT_3_5KG = '3_5kg';

    public const WEIGHT_5_10KG = '5_10kg';

    protected $fillable = [
        'order_id',
        'parcel_type',
        'weight_slab',
        'quantity',
        'declared_value_paise',
        'special_instructions',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'declared_value_paise' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ParcelImage::class);
    }
}
