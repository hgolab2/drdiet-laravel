<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietUserWeeklyItem extends Model
{
    protected $table = 'diet_user_weekly_items';

    protected $fillable = [
        'userWeeklyId',
        'dietWeeklyMealId',
        'mealId',
        'mealItemId',
        'unitCount',
        'calories'
    ];

    public function userWeekly()
    {
        return $this->belongsTo(DietUserWeekly::class, 'userWeeklyId');
    }

    public function dietWeeklyMeal()
    {
        return $this->belongsTo(DietWeeklyMeal::class, 'dietWeeklyMealId');
    }

    public function mealItem()
    {
        return $this->belongsTo(DietItem::class, 'mealItemId');
    }

    public function meal()
    {
        return $this->belongsTo(DietMeal::class, 'mealId');
    }
}
