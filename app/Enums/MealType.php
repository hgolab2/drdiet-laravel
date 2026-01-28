<?php
namespace App\Enums;

enum MealType: int
{
    case Breakfast = 1;
    case Lunch = 2;
    case Dinner = 3;
    case MorningSnack = 4;
    case PreLunch = 5;
    case FatPortion = 6;
    case SugarPortion = 7;
    case DairyPortion = 8;
    case AfternoonSnack2 = 9;
    case AfterDinner =10;
    case CompulsoryShare =11;
    case BreastfeedingShare =12;

    public function label(): string
    {
        return match($this) {
            self::Breakfast => 'الفطور',
            self::Lunch => 'الغداء',
            self::Dinner => 'العشاء',
            self::MorningSnack => 'اسناك صباحی',
            self::PreLunch => 'قبل الغداء',
            self::FatPortion => 'حصص الدهون',
            self::SugarPortion => 'حصص السکریات',
            self::DairyPortion => 'حصص الالبان',
            self::AfternoonSnack2 => 'اسناك العصر',
            self::AfterDinner => 'بعد العشاء',
            self::CompulsoryShare => 'حصة الإجبارية',
            self::BreastfeedingShare => 'حصة الرضاعة',

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
        return null; // اگر پیدا نشد
    }
}
