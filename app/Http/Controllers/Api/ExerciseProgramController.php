<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\ExerciseProgram;
use App\Models\ExerciseProgramItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Enums\ExerciseLavels;
use App\Enums\ExerciseGoals;
use App\Enums\ExerciseLocations;

class ExerciseProgramController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Post(
     *     path="/api/exercise-programs",
     *     summary="ایجاد برنامه ورزشی جدید (با روزها و حرکات یا عضلات)",
     *     tags={"Exercise Programs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="gender", type="string", enum={"male", "female" , "both"}, example="male"),
     *             @OA\Property(property="name", type="string", example="برنامه ۴ هفته‌ای"),
     *             @OA\Property(property="description", type="string", nullable=true, example="برنامه مخصوص سطح متوسط"),
     *             @OA\Property(property="level_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="goal_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="location_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="is_sick", type="integer", nullable=true, example=1, description="اگر 1 باشد، days شامل exercise_id است. اگر 0 باشد days شامل muscle_id است."),
     *
     *             @OA\Property(
     *                 property="days",
     *                 type="object",
     *                 nullable=true,
     *                 description="آرایه‌ای از روزها که هر روز شامل آرایه‌ای از شناسه‌هاست (تمرین‌ها یا عضلات)",
     *                 example={
     *                     "1": {1, 2, 5},
     *                     "2": {3, 4}
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="برنامه ورزشی با موفقیت ایجاد شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="برنامه ورزشی با موفقیت ایجاد شد."),
     *             @OA\Property(property="program_id", type="integer", example=10)
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="دسترسی غیرمجاز",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطا در اعتبارسنجی",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'gender' => 'sometimes|in:male,female,both',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'is_sick' => 'nullable|int',
            'level_id' => 'nullable|integer',
            'goal_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'days' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $program = ExerciseProgram::create($request->only([
            'gender', 'name', 'description', 'level_id', 'goal_id' ,'location_id', 'is_sick'
        ]));

        if ($request->has('days')) {
            if($request->is_sick)
            {
                foreach ($request->days as $day => $exercises) {
                    if (is_array($exercises)) {
                        foreach ($exercises as $exercise_id) {
                            ExerciseProgramItem::create([
                                'exercise_program_id' => $program->id,
                                'exercise_id' => $exercise_id,
                                'day' => (int)$day
                            ]);
                        }
                    }
                }
            }
            else
            {
                foreach ($request->days as $day => $muscles) {
                    if (is_array($muscles)) {
                        foreach ($muscles as $muscle_id) {
                            ExerciseProgramItem::create([
                                'exercise_program_id' => $program->id,
                                'muscle_id' => $muscle_id,
                                'day' => (int)$day
                            ]);
                        }
                    }
                }
            }
        }

        return response()->json([
            'message' => 'برنامه ورزشی با موفقیت ایجاد شد.',
            'program_id' => $program->id
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/exercise-programs/{id}",
     *     summary="نمایش جزئیات یک برنامه ورزشی",
     *     tags={"Exercise Programs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="شناسه برنامه", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="موفق"),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="برنامه یافت نشد")
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $exerciseProgram = ExerciseProgram::with('items.exercise.goals', 'items.exercise.locations')->find($id);

        if (!$exerciseProgram) {
            return response()->json(['message' => 'برنامه یافت نشد.'], 404);
        }

        // گروه‌بندی آیتم‌ها بر اساس روز
        $days = $exerciseProgram->items->groupBy('day')->map(function ($items, $day) {
            return $items->map(function($item) {
                $ex = $item->exercise;
                if (!$ex) {
                    return [
                        'id' => $item->id,
                        'exercise_id' => $item->exercise_id,
                        'muscle_id' => $item->muscle_id,
                        'muscle' => $item->muscle?->name_ar,
                    ];
                }

                return [
                    'id' => $item->id,
                    'exercise_id' => $item->exercise_id,
                    'muscle_id' => $item->muscle_id,
                    'muscle' => $item->muscle?->name_ar,
                    'name_ar' => $ex->name_ar,
                    'name_en' => $ex->name_en,
                    'name_fa' => $ex->name_fa,
                    'home_type' => $ex->home_type,
                    'target_muscle' => $ex->target_muscle,
                    'description_ar' => $ex->description_ar,
                    'imageUrl1' => $ex->image1?->url(),
                    'imageUrl2' => $ex->image2?->url(),
                    'video' => $ex->video,
                    'goals' => $ex->goals->map(fn($t) => [
                        'id' => $t->goal_id,
                        'label' => $t->goal_id ? ExerciseGoals::from($t->goal_id)->label() : null,
                    ]),
                    'locations' => $ex->locations->map(fn($t) => [
                        'id' => $t->location_id,
                        'label' => $t->location_id ? ExerciseLocations::from($t->location_id)->label() : null,
                    ]),
                ];
            });
        });

        return response()->json([
            'id' => $exerciseProgram->id,
            'gender' => $exerciseProgram->gender,
            'name' => $exerciseProgram->name,
            'description' => $exerciseProgram->description,
            'is_sick' => $exerciseProgram->is_sick,
            'level_id' => $exerciseProgram->level_id,
            'level' => $exerciseProgram->level_id ? ExerciseLavels::from($exerciseProgram->level_id)->label() : null,
            'goal_id' => $exerciseProgram->goal_id,
            'goal' => $exerciseProgram->goal_id ? ExerciseGoals::from($exerciseProgram->goal_id)->label() : null,
            'location_id' => $exerciseProgram->location_id,
            'location' => $exerciseProgram->location_id ? ExerciseLocations::from($exerciseProgram->location_id)->label() : null,
            'days' => $days,
        ]);
    }


    /**
     * @OA\Put(
     *     path="/api/exercise-programs/{id}",
     *     summary="ویرایش برنامه ورزشی (روزها + تمرین‌ها یا عضلات)",
     *     tags={"Exercise Programs"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه برنامه ورزشی",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="gender", type="string", enum={"male", "female" , "both"}, example="male"),
     *             @OA\Property(property="name", type="string", example="برنامه ۴ هفته‌ای"),
     *             @OA\Property(property="description", type="string", nullable=true, example="برنامه مخصوص سطح متوسط"),
     *             @OA\Property(property="level_id", type="integer", nullable=true, example=2),
     *             @OA\Property(property="goal_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="location_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="is_sick", type="integer", nullable=true, example=1,
     *                 description="اگر 1 باشد days شامل exercise_id است؛ اگر 0 باشد days شامل muscle_id است."
     *             ),
     *
     *             @OA\Property(
     *                 property="days",
     *                 type="object",
     *                 nullable=true,
     *                 description="Object از روزها؛ برای هر روز یک آرایه از شناسه تمرین یا عضله",
     *                 example={
     *                     "1": {1, 2, 5},
     *                     "2": {3, 4}
     *                 }
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="برنامه با موفقیت ویرایش شد.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="برنامه با موفقیت ویرایش شد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="دسترسی غیرمجاز",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="خطا در اعتبارسنجی",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $program = ExerciseProgram::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'gender' => 'sometimes|in:male,female,both',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_sick' => 'nullable|int',
            'level_id' => 'nullable|integer',
            'goal_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'days' => 'array|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // بروزرسانی اطلاعات اصلی برنامه
        $program->update($request->only(['gender','name', 'description', 'level_id', 'goal_id' ,'location_id',  'is_sick']));

        // حذف آیتم‌های قدیمی و افزودن آیتم‌های جدید
        $program->items()->delete();

        if ($request->has('days')) {
            if($request->is_sick)
            {
                foreach ($request->days as $day => $exercises) {
                    if (is_array($exercises)) {
                        foreach ($exercises as $exercise_id) {
                            ExerciseProgramItem::create([
                                'exercise_program_id' => $program->id,
                                'exercise_id' => $exercise_id,
                                'day' => (int)$day
                            ]);
                        }
                    }
                }
            }
            else
            {
                foreach ($request->days as $day => $muscles) {
                    if (is_array($muscles)) {
                        foreach ($muscles as $muscle_id) {
                            ExerciseProgramItem::create([
                                'exercise_program_id' => $program->id,
                                'muscle_id' => $muscle_id,
                                'day' => (int)$day
                            ]);
                        }
                    }
                }
            }
        }

        return response()->json(['message' => 'برنامه با موفقیت ویرایش شد.']);
    }


    /**
     * @OA\Get(
     *     path="/api/exercise-programs",
     *     summary="لیست برنامه‌های ورزشی",
     *     tags={"Exercise Programs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="name", in="query", description="جستجو بر اساس نام", @OA\Schema(type="string")),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="جنسیت کاربر",
     *         @OA\Schema(type="string", enum={"male","female","both"})
     *     ),
     *     @OA\Parameter(name="pagesize", in="query", description="تعداد در هر صفحه", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="موفق")
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'sales_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pageSize = $request->pagesize ?? 20;
        $query = ExerciseProgram::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('gender')) {
            if($request->filled('gender') == 'both')
            {
                $query->where('gender', $request->gender);
            }
            else
            {
                $gender = $request->gender;
                $query->where(function ($q) use ($gender) {
                    $q->where('gender', 'both')
                    ->orwhere('gender', $gender);
                });
            }
        }
        $total = $query->count();
        $data = $query->orderBy('id', 'desc')->paginate($pageSize);

        $data = collect($data->items())->map(function ($exerciseProgram) {
            // گروه‌بندی آیتم‌ها بر اساس روز
            $days = $exerciseProgram->items->groupBy('day')->map(function ($items, $day) {
                return $items->map(function($item) {
                    $ex = $item->exercise;
                    if (!$ex) {
                        return [
                            'id' => $item->id,
                            'exercise_id' => $item->exercise_id,
                            'muscle_id' => $item->muscle_id,
                            'muscle' => $item->muscle?->name_ar,
                        ];
                    }

                    return [
                        'id' => $item->id,
                        'exercise_id' => $item->exercise_id,
                        'muscle_id' => $item->muscle_id,
                        'muscle' => $item->muscle?->name_ar,
                        'name_ar' => $ex->name_ar,
                        'name_en' => $ex->name_en,
                        'name_fa' => $ex->name_fa,
                        'home_type' => $ex->home_type,
                        'target_muscle' => $ex->target_muscle,
                        'description_ar' => $ex->description_ar,
                        'imageUrl1' => $ex->image1?->url(),
                        'imageUrl2' => $ex->image2?->url(),
                        'video' => $ex->video,
                        'goals' => $ex->goals->map(fn($t) => [
                            'id' => $t->goal_id,
                            'label' => $t->goal_id ? ExerciseGoals::from($t->goal_id)->label() : null,
                        ]),
                        'locations' => $ex->locations->map(fn($t) => [
                            'id' => $t->location_id,
                            'label' => $t->location_id ? ExerciseLocations::from($t->location_id)->label() : null,
                        ]),
                    ];
                });
            });

            return [
                'id' => $exerciseProgram->id,
                'gender' => $exerciseProgram->gender,
                'name' => $exerciseProgram->name,
                'description' => $exerciseProgram->description,
                'is_sick' => $exerciseProgram->is_sick,
                'level_id' => $exerciseProgram->level_id,
                'level' => $exerciseProgram->level_id ? ExerciseLavels::from($exerciseProgram->level_id)->label() : null,
                'goal_id' => $exerciseProgram->goal_id,
                'goal' => $exerciseProgram->goal_id ? ExerciseGoals::from($exerciseProgram->goal_id)->label() : null,
                'location_id' => $exerciseProgram->location_id,
                'location' => $exerciseProgram->location_id ? ExerciseLocations::from($exerciseProgram->location_id)->label() : null,
                'days' => $days,
            ];
        });

        return response()->json([
            'result' => $data,
            'totalCount' => $total
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/exercise-programs/{id}",
     *     summary="حذف برنامه ورزشی",
     *     tags={"Exercise Programs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="شناسه برنامه", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="برنامه با موفقیت حذف شد"),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="برنامه یافت نشد")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $program = ExerciseProgram::findOrFail($id);
        $program->items()->delete();
        $program->delete();

        return response()->json(['message' => 'برنامه با موفقیت حذف شد.']);
    }

}
