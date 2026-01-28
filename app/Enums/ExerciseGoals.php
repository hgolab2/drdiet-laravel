<?php

namespace App\Enums;

enum ExerciseGoals: int
{
    case کاهش_وزن = 1;
    case درمانی = 2;
    case عضله_سازی = 3;

    public function label(): string
    {
        return match ($this) {
            self::کاهش_وزن => 'تخسيس الوزن',   // کاهش وزن
            self::درمانی => 'علاجي',           // درمانی
            self::عضله_سازی => 'بناء العضلات', // عضله‌سازی
        };
    }
}
