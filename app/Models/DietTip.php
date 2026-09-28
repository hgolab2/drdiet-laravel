<?php

namespace App\Models;

use App\Enums\DietType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietTip extends Model
{
    protected $table = 'diet_tips';

    protected $fillable = [
        'text',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function types(): HasMany
    {
        return $this->hasMany(DietTipType::class, 'diet_tip_id');
    }

    // نکات فعال مربوط به یک نوع رژیم + نکات عمومی، به ترتیب اولویت (عدد کمتر = مهم‌تر)
    public static function forDietType(?int $dietTypeId): array
    {
        return static::query()
            ->where('is_active', true)
            ->where(function ($query) use ($dietTypeId) {
                $query->whereDoesntHave('types');
                if ($dietTypeId) {
                    $query->orWhereHas('types', fn ($q) => $q->where('diet_type_id', $dietTypeId));
                }
            })
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(fn ($tip) => [
                'id' => $tip->id,
                'text' => $tip->text,
                'priority' => $tip->priority,
            ])
            ->all();
    }

    public function toOutput(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'diet_types' => $this->types->map(fn ($type) => [
                'id' => $type->diet_type_id,
                'label' => DietType::tryFrom($type->diet_type_id)?->label(),
            ])->values(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
