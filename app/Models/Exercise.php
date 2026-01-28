<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'gender',
        'name_ar',
        'name_en',
        'name_fa',
        'home_type',
        'target_muscle',
        'description_ar',
        'image_id1',
        'image_id2',
        'video',
    ];

    public function goals()
    {
        return $this->hasMany(ExerciseGoal::class, 'exercise_id');
    }

    public function locations()
    {
        return $this->hasMany(ExerciseLocation::class, 'exercise_id');
    }

    // اگر تصاویر و ویدیوها در جدول Media یا Files باشند، می‌توان رابطه تعریف کرد:
    public function image1()
    {
        return $this->belongsTo(Image::class, 'image_id1');
    }

    public function image2()
    {
        return $this->belongsTo(Image::class, 'image_id2');
    }

    /*public function video()
    {
        return $this->belongsTo(Image::class, 'video_id');
    }*/

    public function muscles()
    {
        return $this->belongsToMany(
            Muscle::class,
            'exercise_muscle',
            'exercise_id',
            'muscle_id'
        )->withTimestamps();
    }
}
