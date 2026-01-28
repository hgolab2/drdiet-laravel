<?php

namespace App\Enums;

enum HasDietHistory: int
{
    case در_شش_ماه_گذشته = 1;
    case قبلاً_گرفتم = 2;
    case نگرفتم = 3;

    public function label(): string
    {
        return match ($this) {
            self::در_شش_ماه_گذشته => 'لقد اتبعتُ نظاماً غذائياً خلال الأشهر الستة الماضية.',
            self::قبلاً_گرفتم => 'لديّ تجربة سابقة مع الأنظمة الغذائية.',
            self::نگرفتم => 'ليست لديّ أي تجربة مع الأنظمة الغذائية.',
        };

    }
}
