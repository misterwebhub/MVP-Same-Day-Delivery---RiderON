<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteScheduleDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'route_schedule_id',
        'date',
        'is_cancelled',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_cancelled' => 'boolean',
        ];
    }

    public function routeSchedule(): BelongsTo
    {
        return $this->belongsTo(RouteSchedule::class);
    }
}
