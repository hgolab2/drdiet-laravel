<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietWeekly extends Model
{
    protected $table = 'diet_weekly';

    protected $fillable = ['name' , 'food_type_id' ,'weight_goal_id' , 'type'];

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
