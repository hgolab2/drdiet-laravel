<?php

namespace App\Helpers;

use App\Enums\DailyActivityLevel;
use App\Enums\DietType;
use App\Enums\FoodType;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

class CalorieCalculator
{
    // ضریب کالری مازاد برای رژیم‌های افزایش وزن
    const GAIN_SURPLUS = 0.15;

    // کالری اضافه دوران شیردهی
    const BREASTFEEDING_EXTRA = 400;

    /**
     * کالری هدف روزانه کاربر (BMR → TDEE → بارداری/شیردهی → استخوان‌بندی → کسر/مازاد → حد ایمنی).
     * اگر وزن ارسال نشود، وزن فعلی کاربر (و در نبود آن وزن اولیه) استفاده می‌شود.
     *
     * @throws InvalidArgumentException در صورت ناقص بودن اطلاعات کاربر
     */
    public static function forUser(User $user, $weight = null): float
    {
        if (!$user->birth_date) {
            throw new InvalidArgumentException('تاریخ تولد موجود نیست.');
        }

        $gender = $user->gender;
        if (!in_array($gender, ['male', 'female'], true)) {
            throw new InvalidArgumentException('جنسیت نامعتبر است.');
        }

        $activityLevel = DailyActivityLevel::tryFrom((int) $user->daily_activity_level);
        if (!$activityLevel) {
            throw new InvalidArgumentException('سطح فعالیت نامعتبر است.');
        }

        $weight = (float) ($weight ?? $user->current_weight ?? $user->weight);
        $height = (float) $user->height;
        if ($weight <= 0 || $height <= 0) {
            throw new InvalidArgumentException('وزن یا قد کاربر موجود نیست.');
        }

        $age = Carbon::parse($user->birth_date)->age;
        $isMale = $gender === 'male';

        // ۱- BMR
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + ($isMale ? 5 : -161);

        // ۲- TDEE
        $tdee = $bmr * match ($activityLevel) {
            DailyActivityLevel::سبک => 1.2,
            DailyActivityLevel::متوسط => 1.375,
            DailyActivityLevel::شدید => 1.55,
            DailyActivityLevel::بسیار_شدید => 1.725,
        };

        // ۳- کالری بارداری (سه ماهه دوم و سوم) و شیردهی
        $pregnancyWeek = $user->currentPregnancyWeek();
        // نوع غذای «بارداری و شیردهی» بدون هفته بارداری، شیردهی حساب می‌شود
        $isMotherFoodType = !$isMale && (int) $user->food_type_id === FoodType::الحمل_والرضاعة->value;
        $isBreastfeeding = $isMotherFoodType && $pregnancyWeek === 0;

        if ($pregnancyWeek >= 28) {
            $tdee += 450;
        } elseif ($pregnancyWeek >= 14) {
            $tdee += 340;
        } elseif ($isBreastfeeding) {
            $tdee += self::BREASTFEEDING_EXTRA;
        }

        // ۴- تعدیل بر اساس استخوان‌بندی (قد / دور مچ)
        $tdee *= self::frameFactor($isMale, $height, (float) $user->wrist_size);

        // ۵- کسر یا مازاد کالری بر اساس نوع رژیم
        $dietType = DietType::tryFrom((int) $user->diet_type_id);
        $target = match (true) {
            in_array($dietType, [DietType::افزایش_وزن, DietType::سندرم_روده_تحریک_پذیر_افزایشی], true)
                => $tdee * (1 + self::GAIN_SURPLUS),
            // در بارداری و شیردهی کسر کالری اعمال نمی‌شود
            $dietType === DietType::تثبیت_وزن,
            $dietType === DietType::بارداری,
            $isMotherFoodType,
            $pregnancyWeek > 0
                => $tdee,
            default => $tdee * (1 - self::deficit($weight, $height)),
        };

        // ۶- حد ایمنی سلامت
        return round(max($target, $isMale ? 1400 : 1100));
    }

    private static function frameFactor(bool $isMale, float $height, float $wrist): float
    {
        if ($wrist <= 0) {
            return 1.0;
        }

        $ratio = $height / $wrist;
        [$large, $small] = $isMale ? [9.6, 10.4] : [10.1, 11.0];

        if ($ratio < $large) {
            return 1.05; // استخوان درشت
        }
        if ($ratio > $small) {
            return 0.97; // استخوان ریز
        }

        return 1.0;
    }

    private static function deficit(float $weight, float $height): float
    {
        $bmi = $weight / (($height / 100) ** 2);

        if ($bmi >= 30) {
            return 0.40;
        }
        if ($bmi >= 25) {
            return 0.35;
        }

        return 0.25;
    }
}
