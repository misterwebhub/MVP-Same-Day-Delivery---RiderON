<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryPartner extends Model
{
    use HasFactory, SoftDeletes;

    public const VEHICLE_TRAIN = 'train';

    public const VEHICLE_BUS = 'bus';

    public const VEHICLE_BIKE = 'bike';

    public const VEHICLE_ON_FOOT = 'on_foot';

    public const VERIFICATION_PENDING = 'pending';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'partner_code',
        'photo_url',
        'vehicle_type',
        'id_proof_type',
        'id_proof_number_encrypted',
        'verification_status',
        'is_active',
        'rating_avg',
        'completed_deliveries_count',
        'current_home_city_id',
    ];

    protected $hidden = [
        'id_proof_number_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rating_avg' => 'decimal:2',
            'completed_deliveries_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentHomeCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'current_home_city_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'partner_id');
    }
}
