<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseUsersProgramItem extends Model
{
    use HasFactory;

    protected $table = 'exercise_users_program_items';

    protected $fillable = [
        'exercise_program_item_id',
        'exercise_users_program_id',
        'exercise_id',
        'set',
        'frequency',
        'user_id',
    ];

    public function program()
    {
        return $this->belongsTo(ExerciseUsersProgram::class, 'exercise_users_program_id');
    }

    public function item()
    {
        return $this->belongsTo(ExerciseProgramItem::class, 'exercise_program_item_id');
    }

    /**
     * آیتم برنامه‌ی اصلی (روز و عضله) این تمرین.
     * اگر آیتم اصلی حذف شده باشد (ویرایش قدیمی برنامه همه‌ی آیتم‌ها را حذف و دوباره می‌ساخت)،
     * آیتم هم‌جایگاه از آیتم‌های فعلی همان برنامه برگردانده می‌شود؛ به شرط برابر بودن تعداد آیتم‌ها.
     */
    public function templateItem(): ?ExerciseProgramItem
    {
        if ($this->item) {
            return $this->item;
        }

        static $maps = [];
        $programId = $this->exercise_users_program_id;
        if (!array_key_exists($programId, $maps)) {
            $maps[$programId] = [];
            $templateId = ExerciseUsersProgram::where('id', $programId)->value('exercise_program_id');
            if ($templateId) {
                $templateItems = ExerciseProgramItem::where('exercise_program_id', $templateId)
                    ->orderBy('id')->get()->values();
                $userItemIds = self::where('exercise_users_program_id', $programId)
                    ->orderBy('id')->pluck('id')->values();
                if ($templateItems->count() === $userItemIds->count()) {
                    foreach ($userItemIds as $index => $userItemId) {
                        $maps[$programId][$userItemId] = $templateItems[$index];
                    }
                }
            }
        }

        return $maps[$programId][$this->id] ?? null;
    }

    public function exercise()
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
