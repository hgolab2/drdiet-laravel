<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietItem extends Model
{
    protected $table = 'diet_item';

    protected $fillable = [
        'name',
        'unit',
        'caloriesGram',
        'weightUnit',
        'foodCultureId',
        'atLeast',
    ];

    public $timestamps = true;
}
