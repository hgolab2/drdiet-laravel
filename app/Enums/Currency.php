<?php

namespace App\Enums;

enum Currency: int
{
    case USD = 1;
    case IQD = 2;
    case BHD = 3;
    case OMR = 4;
    case SYP = 5;
    case LBP = 6;
    case TRY = 7;
    case EGP = 8;
    case IRT = 9;
    case KWD = 10;
    case DZD = 11;
    case QAR = 12;
    case AED = 13;
    case EUR = 14;
    case ILS = 15;

    public function label(): string
    {
        return match($this) {
            self::USD => 'دلار',
            self::IQD => 'دینار عراقی',
            self::BHD => 'دینار بحرین',
            self::OMR => 'ریال عمان',
            self::SYP => 'لیره سوریة',
            self::LBP => 'لیره لبنان',
            self::TRY => 'لیره ترکیا',
            self::EGP => 'جنیه',
            self::IRT => 'تومان',
            self::KWD => 'دینار کویت',
            self::DZD => 'دینار جزائري',
            self::QAR => 'ریال قطر',
            self::AED => 'درهم امارات',
            self::EUR => 'یورو',
            self::ILS => 'شیکل',
        };
    }

    // کد ارز (IRT برای تومان کد رسمی ISO نیست)
    public function code(): string
    {
        return $this->name;
    }
}
