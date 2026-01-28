<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, HasRoles;
    /*
    protected $table = 'account_user';
    protected $fillable = [
        'username', 'phone', 'email', 'first_name', 'last_name','gender', 'password','is_superuser','is_staff', 'birth_day','is_active', 'date_joined','otp','organization_id', 'otp_created_at', 'last_login', 'deleted', 'deleted_at',
    ];
    protected $hidden = [
        'password', 'remember_token',
    ];*/
    protected $table = 'diet_users';
    protected $fillable = [
        'phone',
        'email',
        'first_name',
        'last_name',
        'gender',
        'password',
        'is_superuser',
        'birth_date',
        'inactive',
        'login_token',
        'food_culture',
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
        'food_type_id',
        'package',
        'expire_at'/*,
        'ai_description'*/
    ];
    protected $hidden = [
        'password',
    ];
    public function generateOtp()
    {
        $this->otp = rand(100000, 999999);
        $this->otp_created_at = now();
        $this->save();
    }
    public function setRememberToken($value){}
    public function fullname()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function photo()
    {
        $img = '/upload/images/avatar_man.png';
        return $img;
    }


    // روابط
    public function dietUserWeeklies()
    {
        return $this->hasMany(DietUserWeekly::class, 'userId');
    }


}
