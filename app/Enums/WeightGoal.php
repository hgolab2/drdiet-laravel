<?php

namespace App\Enums;

enum WeightGoal: int
{
    case زيادة_الوزن = 1;
    case نقصان_الوزن = 2;
    case تثبيت_الوزن = 3;

    public function label(): string
    {
        return match ($this) {
            self::زيادة_الوزن => 'زيادة الوزن',
            self::نقصان_الوزن => 'نقصان الوزن',
            self::تثبيت_الوزن => 'تثبيت_الوزن',
        };
    }
}
