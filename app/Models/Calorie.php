<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calorie extends Model
{
    protected $table = 'calorie';

    protected $fillable = [
        'dietTypeId',
        'dinner',
        'dinnerType',
        'afternoonSnack2',
        'afternoonSnack2Type',
        'lunch',
        'lunchType',
        'preLunch',
        'preLunchType',
        'morningSnack',
        'morningSnackType',
        'breakfast',
        'breakfastType',
        'sugarPortion',
        'sugarPortionType',
        'fatPortion',
        'fatPortionType',
        'dairyPortion',
        'dairyPortionType',
        'afterDinner',
        'afterDinnerType',
        'compulsoryShare',
        'compulsoryShareType',
        'breastfeedingShare',
        'breastfeedingShareType',
    ];

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
}
