<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RouteSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_id',
        'departure_time',
        'arrival_time',
        'days_of_week',
        'booking_cutoff_minutes_before',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week' => 'array',
            'booking_cutoff_minutes_before' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function scheduleDates(): HasMany
    {
        return $this->hasMany(RouteScheduleDate::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
