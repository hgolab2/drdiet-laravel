<?php

namespace App\Enums;

enum DailyActivityLevel: int
{
    case سبک = 1;
    case متوسط = 2;
    case شدید = 3;
    case بسیار_شدید = 4;

    public function label(): string
    {
        return match ($this) {
            self::سبک => 'نشاط بدني خفيف',
            self::متوسط => 'نشاط بدني متوسط',
            self::شدید => 'نشاط بدني شديد',
            self::بسیار_شدید => 'نشاط بدني شديد جداً',
        };
    }
}
