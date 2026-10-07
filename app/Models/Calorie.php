<?php

namespace App\Models;

use App\Enums\DietType;
use App\Enums\FoodType;
use Illuminate\Database\Eloquent\Model;

class Calorie extends Model
{
    protected $table = 'calorie';

    protected $fillable = [
        'dietTypeId',
        'dinner',
        'dinnerType',
        'afternoonSnack2',
        'afternoonSnack2Type',
        'lunch',
        'lunchType',
        'preLunch',
        'preLunchType',
        'morningSnack',
        'morningSnackType',
        'breakfast',
        'breakfastType',
        'sugarPortion',
        'sugarPortionType',
        'fatPortion',
        'fatPortionType',
        'dairyPortion',
        'dairyPortionType',
        'afterDinner',
        'afterDinnerType',
        'compulsoryShare',
        'compulsoryShareType',
        'breastfeedingShare',
        'breastfeedingShareType',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * ردیف کالری متناسب با نوع غذای برنامه هفتگی؛
     * اگر برای آن ردیفی نبود، ردیف diet_type_id کاربر (کاهش/افزایش/تثبیت وزن).
     */
    public static function forWeekly(?int $weeklyId, ?int $userDietTypeId): ?self
    {
        $foodType = FoodType::tryFrom((int) DietWeekly::whereKey($weeklyId)->value('food_type_id'));

        $dietTypeIds = array_map(fn (DietType $type) => $type->value, $foodType?->dietTypes($userDietTypeId) ?? []);
        $dietTypeIds[] = $userDietTypeId;

        foreach (array_unique(array_filter($dietTypeIds)) as $dietTypeId) {
            $calorie = self::where('dietTypeId', $dietTypeId)->first();
            if ($calorie) {
                return $calorie;
            }
        }

        return null;
    }
}
