<?php

namespace App\Helpers;

use App\Enums\DailyActivityLevel;
use App\Enums\DietType;

class CalorieCalculator
{
    /**
     * کالری هدف روزانه کاربر
     *
     * @param string $gender male | female
     */
    public static function target(
        string $gender,
        int $age,
        ?float $height,
        ?float $weight,
        ?float $wrist,
        ?int $pregnancyWeek,
        DailyActivityLevel $activityLevel,
        ?int $dietTypeId
    ): float {
        $height = (float) $height;
        $weight = (float) $weight;
        $isFemale = $gender === 'female';
        $pregnancyWeek = $isFemale ? (int) $pregnancyWeek : 0;

        // مرحله ۱: BMR
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + ($isFemale ? -161 : 5);

        // مرحله ۲: TDEE
        $multiplier = match ($activityLevel) {
            DailyActivityLevel::سبک => 1.2,
            DailyActivityLevel::متوسط => 1.375,
            DailyActivityLevel::شدید => 1.55,
            DailyActivityLevel::بسیار_شدید => 1.725,
        };
        $tdee = $bmr * $multiplier;

        // مرحله ۳: کالری بارداری
        if ($pregnancyWeek >= 14 && $pregnancyWeek <= 27) {
            $tdee += 340; // سه ماهه دوم
        } elseif ($pregnancyWeek >= 28) {
            $tdee += 450; // سه ماهه سوم
        }

        // مرحله ۴: استخوان‌بندی (دور مچ)
        if ($wrist > 0) {
            $frameRatio = $height / $wrist;
            [$largeBelow, $smallAbove] = $isFemale ? [10.1, 11.0] : [9.6, 10.4];
            if ($frameRatio < $largeBelow) {
                $tdee *= 1.05; // استخوان درشت
            } elseif ($frameRatio > $smallAbove) {
                $tdee *= 0.97; // استخوان ریز
            }
        }

        // مرحله ۵: کسر/افزایش کالری بر اساس نوع رژیم
        switch (DietType::tryFrom((int) $dietTypeId)) {
            case DietType::کاهش_وزن:
                $bmi = $height > 0 ? $weight / (($height / 100) ** 2) : 0;
                if ($pregnancyWeek > 0) {
                    $deficit = 0.0; // در بارداری کسر کالری اعمال نمی‌شود
                } elseif ($bmi < 25) {
                    $deficit = 0.25;
                } elseif ($bmi < 30) {
                    $deficit = 0.35;
                } else {
                    $deficit = 0.40;
                }
                $target = $tdee * (1 - $deficit);
                break;
            case DietType::افزایش_وزن:
                $target = $tdee + match ($activityLevel) {
                    DailyActivityLevel::سبک => 500,
                    DailyActivityLevel::متوسط => 900,
                    DailyActivityLevel::شدید,
                    DailyActivityLevel::بسیار_شدید => 1100,
                };
                break;
            default:
                $target = $tdee;
                break;
        }

        // مرحله ۶: حد ایمنی
        return max($target, $isFemale ? 1100 : 1400);
    }
}
