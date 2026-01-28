<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'diet_users';

    protected $fillable = [
        'is_superuser',
        'gender',
        'first_name',
        'last_name',
        'food_culture',
        'phone',
        'email',
        'password',
        'birth_date',
        'height',
        'weight',
        'wrist_size',
        'pregnancy_week',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
        'address',
        'diet_type_id',
        'daily_activity_level',
        'diet_goal',
        'has_diet_history',
        'diet_history',
        'package',
        'inactive'
    ];

    protected $hidden = ['password'];
    public function dietUserWeeklies()
    {
        return $this->hasMany(DietUserWeekly::class, 'userId');
    }

}
