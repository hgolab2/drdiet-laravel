<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietMeal extends Model
{
    protected $table = 'diet_meals';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'name',
        'imageId',
        'description',
        'user_id',
        'status',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
