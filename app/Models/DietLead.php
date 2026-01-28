<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @OA\Schema(
 *   schema="DietLead",
 *   type="object",
 *   @OA\Property(property="gender", type="string", example="male"),
 *   @OA\Property(property="height", type="integer", example=175),
 *   @OA\Property(property="weight", type="integer", example=80),
 *   @OA\Property(property="phone", type="string", example="+989123456789"),
 *   @OA\Property(property="code", type="string", example="0081"),
 *   @OA\Property(property="country", type="string", example="Iran"),
 *   @OA\Property(property="age", type="integer", example=30),
 *   @OA\Property(property="source", type="string", example="Instagram"),
 *   @OA\Property(property="status", type="int", example="1"),
 *   @OA\Property(property="user_status", type="int", example="1"),
 *   @OA\Property(property="expert_id", type="integer", nullable=true),
 *   @OA\Property(property="notes", type="string", example="علاقه‌مند به برنامه رژیم آنلاین"),
 *   @OA\Property(property="ip_address", type="string", example="192.168.1.10"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class DietLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'gender',
        'name',
        'height',
        'weight',
        'phone',
        'code',
        'country',
        'age',
        'source',
        'status',
        'user_status',
        'expert_id',
        'notes',
        'ip_address',
    ];

    public function expert()
    {
        return $this->belongsTo(User::class, 'expert_id');
    }
    public function statusValue()
    {
        $value = '';
        switch($this->status)
        {
            case 1:
                $value = 'تم التواصل';
                break;
            case 2:
                $value = 'رقم غلط';
                break;
            case 3:
                $value = 'ما في واتساب';
                break;
        }
        return $value;
    }
    public function userStatusValue()
    {
        $value = '';
        switch($this->user_status)
        {
            case 1:
                $value = 'تم الاشتراک';
                break;
            case 2:
                $value = 'اقساط';
                break;
            case 3:
                $value = 'بانتظار الدفع';
                break;
            case 4:
                $value = 'بانتظار نتيجة تحاليل';
                break;
            case 5:
                $value = 'تأجيل لأول الشهر';
                break;
            case 6:
                $value = 'عدم الرد';
                break;
            case 7:
                $value = 'سعر غالي';
                break;
        }
        return $value;
    }
}
