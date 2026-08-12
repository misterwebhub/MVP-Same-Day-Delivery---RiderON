<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Station extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_RAILWAY = 'railway';

    public const TYPE_BUS_STAND = 'bus_stand';

    protected $fillable = [
        'city_id',
        'name',
        'code',
        'type',
        'latitude',
        'longitude',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function savedContacts(): HasMany
    {
        return $this->hasMany(SavedContact::class);
    }

    public function originRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'origin_station_id');
    }

    public function destinationRoutes(): HasMany
    {
        return $this->hasMany(Route::class, 'destination_station_id');
    }
}
