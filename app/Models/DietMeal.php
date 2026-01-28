<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietMeal extends Model
{
    protected $table = 'diet_meals';

    protected $fillable = [
        'name',
        'imageId',
        'description'
    ];

    public function mealTypes()
    {
        return $this->hasMany(DietMealType::class, 'meal_id');
    }

    public function mealCategories()
    {
        return $this->hasMany(DietMealCategory::class, 'meal_id');
    }

    public function image()
    {
        return $this->belongsTo(Image::class, 'imageId');
    }

    public function foodCultures()
    {
        return $this->hasMany(DietMealCulture::class, 'meal_id');
    }



    public function items()
    {
        return $this->hasMany(DietMealItem::class, 'mealId');
    }
}
