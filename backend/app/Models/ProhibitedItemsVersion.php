<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProhibitedItemsVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_label',
        'content',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function orderDeclarations(): HasMany
    {
        return $this->hasMany(OrderDeclaration::class);
    }
}
