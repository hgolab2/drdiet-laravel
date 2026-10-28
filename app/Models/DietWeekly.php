<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietWeekly extends Model
{
    protected $table = 'diet_weekly';

    protected $fillable = ['name' , 'food_type_id' ,'weight_goal_id' , 'type', 'days_count'];

    public const DEFAULT_DAYS = 7;
    public const MAX_DAYS = 31;

    // طول چرخه برنامه؛ روزهای برنامه کاربر بعد از این تعداد از روز ۱ تکرار می‌شوند
    public function cycleDays(): int
    {
        $days = (int) ($this->days_count ?? self::DEFAULT_DAYS);
        return $days > 0 ? $days : self::DEFAULT_DAYS;
    }

    public function meals()
    {
        return $this->hasMany(DietWeeklyMeal::class, 'diet_weekly_id');
    }

    public function foodCultures()
    {
        return $this->hasMany(DietWeeklyCulture::class, 'diet_weekly_id');
    }
    public function cultures()
    {
        return $this->hasMany(DietWeeklyCulture::class, 'diet_weekly_id');
    }
    public function types()
    {
        return $this->hasMany(DietWeeklyType::class, 'diet_weekly_id');
    }
}
