<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    use HasFactory;

    public const LANGUAGE_ENGLISH = 'en';

    public const LANGUAGE_HINDI = 'hi';

    protected $fillable = [
        'user_id',
        'preferred_language',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
