<?php

/*namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class DietUser extends Authenticatable
{
    use HasApiTokens, Notifiable, HasFactory, HasRoles;

    protected $table = 'diet_users';

    protected $fillable = [
        'phone', 'email', 'first_name', 'last_name', 'gender', 'password',
        'is_superuser', 'birth_date', 'inactive', 'login_token'
    ];

    protected $hidden = ['password'];

    // روابط
    public function dietUserWeeklies()
    {
        return $this->hasMany(DietUserWeekly::class, 'userId');
    }

    // تابع fullname
    public function fullname()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    // تابع تولید OTP
    public function generateOtp()
    {
        $this->otp = rand(100000, 999999);
        $this->otp_created_at = now();
        $this->save();
    }

    // تابع setRememberToken خالی برای Passport
    public function setRememberToken($value) {}
}*/
