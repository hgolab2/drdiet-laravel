<?php

namespace App\Enums;

enum DietGoal: int
{
    case الصحة = 1;
    case السمنة = 2;
    case سبب_آخر = 3;

    public function label(): string
    {
        return match ($this) {
            self::الصحة => 'الصحة',
            self::السمنة => 'السمنة',
            self::سبب_آخر => 'سبب آخر',
        };
    }
}
