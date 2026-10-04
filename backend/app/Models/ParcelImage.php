<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParcelImage extends Model
{
    use HasFactory;

    public $timestamps = false;

    public const TYPE_PHOTO = 'photo';

    public const TYPE_INVOICE = 'invoice';

    protected $fillable = [
        'parcel_id',
        'storage_path',
        'type',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }
}
