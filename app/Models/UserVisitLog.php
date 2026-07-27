<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVisitLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'page_url',
        'page_path',
        'page_title',
        'referrer_url',
        'ip_address',
        'user_agent',
        'metadata',
        'visited_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'visited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
