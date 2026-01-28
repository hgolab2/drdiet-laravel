<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\FoodCulture;

class DietMealCulture extends Model
{
    protected $table = 'diet_meal_food_culture';
    public $timestamps = false;
    protected $fillable = ['meal_id', 'food_culture_id'];
}
