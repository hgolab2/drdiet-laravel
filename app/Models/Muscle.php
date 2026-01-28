<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Muscle extends Model
{
    use HasFactory;

    protected $table = 'muscle';

    protected $fillable = [
        'name',
        'name_en',
        'name_ar',
    ];

    /**
     * عضلاتی که به تمرین‌ها مرتبط هستند
     */
    public function exercises()
    {
        return $this->belongsToMany(
            Exercise::class,
            'exercise_muscle',
            'muscle_id',
            'exercise_id'
        )->withTimestamps();
    }
}
