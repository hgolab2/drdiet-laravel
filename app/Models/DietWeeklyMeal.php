<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietWeeklyMeal extends Model
{
    protected $table = 'diet_weekly_meals';

    protected $fillable = ['mealId', 'mealTypeId', 'day', 'diet_weekly_id'];

    public function meal()
    {
        return $this->belongsTo(DietMeal::class, 'mealId');
    }
}
