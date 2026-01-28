<?php

namespace App\Enums;

enum FoodCulture: int
{
    case ALGERIAN       = 1;
    case BAHRAINI       = 2;
    case EGYPTIAN       = 3;
    case IRANIAN        = 4;
    case IRAQI          = 5;
    case JORDANIAN      = 6;
    case KUWAITI        = 7;
    case LEBANESE       = 8;
    case MOROCCAN       = 9;
    case OMANI          = 10;
    case PALESTINIAN    = 11;
    case QATARI         = 12;
    case SAUDI          = 13;
    case SYRIAN         = 14;
    case EMIRATI        = 15;
    case TUNISIA        = 16;
    case INTERNATIOLAL  = 17;

    public function label(): string
    {
        return match($this) {
            self::ALGERIAN => 'قائمة الأطعمة الجزائرية',
            self::BAHRAINI => 'قائمة الطعام البحرينية',
            self::EGYPTIAN => 'قائمة الأطعمة المصرية',
            self::IRANIAN => 'قائمة الأطعمة الإيرانية',
            self::IRAQI => 'قائمة الأطعمة العراقية',
            self::JORDANIAN => 'قائمة الأطعمة الأردنية',
            self::KUWAITI => 'قائمة الأطعمة الكويتية',
            self::LEBANESE => 'قائمة الطعام اللبنانية',
            self::MOROCCAN => 'قائمة الأطعمة المغربية',
            self::OMANI => 'قائمة الأطعمة العمانية',
            self::PALESTINIAN => 'قائمة الأطعمة الفلسطينية',
            self::QATARI => 'قائمة الأطعمة القطرية',
            self::SAUDI => 'قائمة الأطعمة السعودية',
            self::SYRIAN => 'قائمة الأطعمة السورية',
            self::EMIRATI => 'قائمة الأطعمة الإماراتية',
            self::TUNISIA => 'قائمة الأطعمة التونسية',
            self::INTERNATIOLAL => 'قائمة الأطعمة العالمية',
        };
    }

}
