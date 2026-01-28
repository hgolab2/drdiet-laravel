<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseUsersProgram extends Model
{
    use HasFactory;

    protected $table = 'exercise_users_programs';

    protected $fillable = [
        'exercise_program_id',
        'user_id',
        'expire_at'
    ];

    public function items()
    {
        return $this->hasMany(ExerciseUsersProgramItem::class, 'exercise_users_program_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(ExerciseProgram::class, 'exercise_program_id');
    }
}
