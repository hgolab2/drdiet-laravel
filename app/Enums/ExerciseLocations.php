<?php

namespace App\Enums;

enum ExerciseLocations: int
{
    case باشگاهی = 1;
    case خانگی = 2;

    public function label(): string
    {
        return match ($this) {
            self::باشگاهی => 'نادي رياضي', // باشگاهی
            self::خانگی => 'منزلي',         // خانگی
        };
    }
}
