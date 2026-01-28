<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseMuscle extends Model
{
    use HasFactory;

    protected $table = 'exercise_muscle';

    protected $fillable = [
        'exercise_id',
        'muscle_id',
    ];

    /**
     * عضله مرتبط
     */
    public function muscle()
    {
        return $this->belongsTo(Muscle::class);
    }

    /**
     * تمرین مرتبط
     */
    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }
}
