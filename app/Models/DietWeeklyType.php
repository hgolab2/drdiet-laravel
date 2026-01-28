<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietWeeklyType extends Model
{
    protected $table = 'diet_weekly_types';
    public $timestamps = false;

    protected $fillable = ['diet_weekly_id', 'type_id'];


}
