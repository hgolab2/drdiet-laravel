<?php
namespace App\Enums;

enum MealCategory: int
{
    // دسته بیماری‌ها و رژیم‌ها
    case قولون        = 1;
    case كوليسترول    = 2;
    case سكري         = 3;
    case كيتو         = 4;
    case MS           = 5;
    case بروتيني      = 6;
    case نباتي        = 7;
    case كبد          = 8;

    // دسته نوع غذا
    case مع_الخبز    = 101;
    case حساء        = 102;
    case سلطة        = 103;
    case مشاوي       = 104;
    case مشروبات     = 105;
    case مع_الأرز    = 106;
    case سندویشات     = 107;

    public function label(): string
    {
        return match($this) {
            // بیماری‌ها و رژیم‌ها
            self::قولون => 'القولون',
            self::كوليسترول => 'الكوليسترول',
            self::سكري => 'سكري',
            self::كيتو => 'كيتو',
            self::MS => 'MS',
            self::بروتيني => 'بروتيني',
            self::نباتي => 'نباتي',
            self::كبد => 'الكبد',

            // نوع الطعام
            self::مع_الخبز => 'مع الخبز',
            self::حساء => 'حساء',
            self::سلطة => 'سلطة',
            self::مشاوي => 'مشاوي',
            self::مشروبات => 'مشروبات',
            self::مع_الأرز => 'مع الأرز',
            self::سندویشات => 'سندویشات',

        };
    }

    public static function getList(): array
    {
        return array_map(fn($case) => [
            'id' => $case->value,
            'name' => lcfirst($case->name),
            'label' => $case->label()
        ], self::cases());
    }

    public static function fromLabel(string $label): ?int
    {
        foreach (self::cases() as $case) {
            if ($case->label() === $label) {
                return $case->value;
            }
        }
        return null;
    }
}
