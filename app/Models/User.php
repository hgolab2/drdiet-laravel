<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'expire_at',
        'max_lead',
        'current_weight',
        'weight_updatedate',
        'pregnancy_week_updated_at'
        /*,
        'ai_description'*/
    ];
    protected $hidden = [
        'password',
    ];

    // حداکثر طول بارداری به هفته؛ بعد از آن کاربر باردار حساب نمی‌شود
    const MAX_PREGNANCY_WEEK = 42;

    protected static function booted(): void
    {
        // با هر تغییر هفته بارداری، تاریخ ثبت آن ذخیره می‌شود
        static::saving(function (User $user) {
            if ($user->isDirty('pregnancy_week')) {
                $user->pregnancy_week_updated_at = $user->pregnancy_week ? now() : null;
            }
        });
    }

    /**
     * هفته فعلی بارداری: هفته ثبت‌شده + هفته‌های سپری‌شده از زمان ثبت.
     * صفر یعنی باردار نیست (یا بارداری به پایان رسیده).
     */
    public function currentPregnancyWeek(): int
    {
        $week = (int) $this->pregnancy_week;
        if ($week <= 0 || $this->gender !== 'female') {
            return 0;
        }

        if ($this->pregnancy_week_updated_at) {
            $week += intdiv((int) \Carbon\Carbon::parse($this->pregnancy_week_updated_at)->diffInDays(now()), 7);
        }

        return $week > self::MAX_PREGNANCY_WEEK ? 0 : $week;
    }

    /**
     * فقط برنامه عادی بر اساس فرهنگ غذایی کاربر است؛
     * برنامه‌های پرپروتئین، نباتی و بیماری‌ها بین‌المللی هستند.
     */
    public function usesFoodCulture(): bool
    {
        return (int) $this->food_type_id === \App\Enums\FoodType::عادي->value && (bool) $this->food_culture;
    }
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

    public function isAdmin()
    {
        return $this->hasAnyRole('super_admin') ? true : false;
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function dietUserWeeklies()
    {
        return $this->hasMany(DietUserWeekly::class, 'userId');
    }
    /**
     * Get the user's registered device tokens.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }
    public function visitLogs(): HasMany
    {
        return $this->hasMany(UserVisitLog::class);
    }
    public function performanceNotes(): HasMany
    {
        return $this->hasMany(UserPerformanceNote::class, 'user_id');
    }
    public function createdPerformanceNotes(): HasMany
    {
        return $this->hasMany(UserPerformanceNote::class, 'created_by');
    }
}
