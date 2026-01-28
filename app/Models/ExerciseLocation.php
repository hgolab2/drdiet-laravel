<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseLocation extends Model
{
    use HasFactory;

    protected $fillable = ['exercise_id', 'location_id'];

    public function exercise()
    {
        return $this->belongsTo(Exercise::class);
    }

}
