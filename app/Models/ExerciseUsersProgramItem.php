<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseUsersProgramItem extends Model
{
    use HasFactory;

    protected $table = 'exercise_users_program_items';

    protected $fillable = [
        'exercise_program_item_id',
        'exercise_users_program_id',
        'exercise_id',
        'set',
        'frequency',
        'user_id',
    ];

    public function program()
    {
        return $this->belongsTo(ExerciseUsersProgram::class, 'exercise_users_program_id');
    }

    public function item()
    {
        return $this->belongsTo(ExerciseProgramItem::class, 'exercise_program_item_id');
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
