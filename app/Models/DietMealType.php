<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\MealType;

class DietMealType extends Model
{
    protected $table = 'diet_meal_meal_type';
    public $timestamps = false;

    protected $fillable = ['meal_id', 'meal_type_id'];


}
