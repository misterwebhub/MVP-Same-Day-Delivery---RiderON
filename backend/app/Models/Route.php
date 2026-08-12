<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Route extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'origin_station_id',
        'destination_station_id',
        'distance_km',
        'estimated_duration_minutes',
        'cutoff_time',
        'max_parcels_per_schedule',
        'waiting_time_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
            'estimated_duration_minutes' => 'integer',
            'max_parcels_per_schedule' => 'integer',
            'waiting_time_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function originStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'origin_station_id');
    }

    public function destinationStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'destination_station_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(RouteSchedule::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
