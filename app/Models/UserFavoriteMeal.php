<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFavoriteMeal extends Model
{
    protected $table = 'user_favorite_meals';

    protected $fillable = [
        'user_id',
        'meal_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'meal_id' => 'integer',
    ];
}
