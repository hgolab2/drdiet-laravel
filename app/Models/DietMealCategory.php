<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietMealCategory extends Model
{
    protected $table = 'diet_meal_category';

        protected $primaryKey = 'id';
    public $timestamps = false; // جدول timestamps نداره

    protected $fillable = [
        'meal_id',
        'meal_category_id',
    ];

    // رابطه با جدول meals
    public function meal()
    {
        return $this->belongsTo(DietMeal::class, 'meal_id');
    }


}
