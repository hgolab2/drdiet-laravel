<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseProgramItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_program_id',
        'exercise_id',
        'muscle_id',
        'day'
    ];

    public function program()
    {
        return $this->belongsTo(ExerciseProgram::class, 'exercise_program_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }

    public function muscle()
    {
        return $this->belongsTo(Muscle::class, 'muscle_id');
    }
}
