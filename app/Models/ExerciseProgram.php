<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'gender',
        'name',
        'description',
        'level_id',
        'goal_id',
        'location_id',
        'is_sick',
    ];

    public function items()
    {
        return $this->hasMany(ExerciseProgramItem::class);
    }

}
