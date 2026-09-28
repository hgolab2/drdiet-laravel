<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietTipType extends Model
{
    protected $table = 'diet_tip_types';
    public $timestamps = false;

    protected $fillable = ['diet_tip_id', 'diet_type_id'];
}
