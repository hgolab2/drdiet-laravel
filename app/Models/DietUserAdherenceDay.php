<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietUserAdherenceDay extends Model
{
    protected $table = 'diet_user_adherence_days';

    protected $fillable = [
        'user_id',
        'user_weekly_id',
        'date',
    ];

    // ثبت یک روز پایبندی؛ اگر امروز قبلاً ثبت شده باشد کاری نمی‌کند
    public static function recordToday(int $userId, ?int $userWeeklyId): void
    {
        $now = now();

        static::query()->insertOrIgnore([
            'user_id' => $userId,
            'user_weekly_id' => $userWeeklyId,
            'date' => $now->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
