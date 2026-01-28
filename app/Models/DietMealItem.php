<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietMealItem extends Model
{
    protected $table = 'diet_meal_items';

    protected $fillable = [
        'itemId', 'mealId', 'percent',
    ];

    public function meal()
    {
        return $this->belongsTo(DietMeal::class, 'mealId');
    }

    public function item()
    {
        return $this->belongsTo(DietItem::class, 'itemId');
    }
}
