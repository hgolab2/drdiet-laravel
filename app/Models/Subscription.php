<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'plan_id',
        'price',
        'payment_id',
        'status',
        'start_date',
    ];

    // ارتباط با کاربر
    public function user()
    {
        return $this->belongsTo(User::class , 'user_id');
    }

}
