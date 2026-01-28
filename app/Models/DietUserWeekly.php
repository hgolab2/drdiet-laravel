<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DietUserWeekly extends Model
{
    protected $table = 'diet_user_weekly';

    protected $fillable = [
        'userId',
        'fromdate',
        'todate',
        'weeklyId',
        'calories',
        'weight',
        'food_type_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function weekly()
    {
        return $this->belongsTo(DietWeekly::class, 'weeklyId');
    }

    public function items()
    {
        return $this->hasMany(DietUserWeeklyItem::class, 'userWeeklyId');
    }
}
