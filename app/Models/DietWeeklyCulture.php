<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\FoodCulture;

class DietWeeklyCulture extends Model
{
    protected $table = 'diet_weekly_cultures';
    public $timestamps = false;

    protected $fillable = ['diet_weekly_id', 'food_culture_id'];


}
