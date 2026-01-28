<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'payment_id',
        'amount',
        'currency',
        'status',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * ارتباط با کاربر (اختیاری)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ارتباط با پرداخت (در صورتی که مدل Payment داری)
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
