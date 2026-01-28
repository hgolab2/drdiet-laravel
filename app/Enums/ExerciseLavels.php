<?php

namespace App\Enums;

enum ExerciseLavels: int
{
    case مبتدی = 1;
    case متوسطه = 2;
    case حرفه_ای = 3;

    public function label(): string
    {
        return match ($this) {
            self::مبتدی => 'مبتدئ',     // عربیِ "مبتدی"
            self::متوسطه => 'متوسط',    // عربیِ "متوسطه"
            self::حرفه_ای => 'محترف',   // عربیِ "حرفه‌ای"
        };
    }
}
