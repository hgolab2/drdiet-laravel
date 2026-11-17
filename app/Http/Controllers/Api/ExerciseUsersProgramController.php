<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseUsersProgram;
use App\Models\ExerciseUsersProgramItem;
use App\Models\ExerciseProgramItem;
use App\Models\ExerciseProgram;
use App\Models\ExerciseMuscle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Enums\ExerciseLavels;
use App\Enums\ExerciseGoals;
use App\Enums\ExerciseLocations;

class ExerciseUsersProgramController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * @OA\Get(
     *     path="/api/exercise-users-programs",
     *     summary="لیست برنامه‌های ورزشی کاربران",
     *     description="دریافت لیست برنامه‌های ورزشی کاربران با امکان فیلتر بر اساس user_id و صفحه‌بندی",
     *     tags={"ExerciseUsersPrograms"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="شناسه کاربر برای فیلتر برنامه‌ها",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم‌ها در هر صفحه",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="لیست برنامه‌های کاربران با موفقیت دریافت شد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="result", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=10),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="برنامه 1"),
     *                     @OA\Property(property="description", type="string", example="توضیحات برنامه"),
     *                     @OA\Property(property="items", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="user_id", type="integer", example=1),
     *                             @OA\Property(property="frequency", type="integer", example=12),
     *                             @OA\Property(property="set", type="integer", example=3),
     *                             @OA\Property(property="name_en", type="string", example="Abs Exercise"),
     *                             @OA\Property(property="name_ar", type="string", example="تمرين البطن"),
     *                             @OA\Property(property="home_type", type="string", example="خانگی بدون وسیله"),
     *                             @OA\Property(property="description_ar", type="string", example="تمرین تقویت شکم"),
     *                             @OA\Property(property="imageUrl1", type="string", nullable=true, example="https://api.example.com/uploads/images/2025/11/img1.webp"),
     *                             @OA\Property(property="imageUrl2", type="string", nullable=true, example="https://api.example.com/uploads/images/2025/11/img2.webp"),
     *                             @OA\Property(property="video", type="string", nullable=true, example="https://api.example.com/uploads/videos/2025/11/video.mp4"),
     *                             @OA\Property(property="goals", type="array",
     *                                 @OA\Items(type="object",
     *                                     @OA\Property(property="id", type="integer", example=1),
     *                                     @OA\Property(property="label", type="string", example="کاهش وزن")
     *                                 )
     *                             ),
     *                             @OA\Property(property="locations", type="array",
     *                                 @OA\Items(type="object",
     *                                     @OA\Property(property="id", type="integer", example=2),
     *                                     @OA\Property(property="label", type="string", example="باشگاه")
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="totalCount", type="integer", example=50)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=403, description="عدم دسترسی به این بخش")
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'sales_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pageSize = $request->pagesize ?? 20;
        $query = ExerciseUsersProgram::with('program');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $total = $query->count();
        $data = $query->orderBy('id', 'desc')->paginate($pageSize);

        $result = $data->map(function ($exerciseUserProgram) {
            $items2 = $exerciseUserProgram->items->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'user' => $item->user?->fullname(),
                    'frequency' => $item->frequency,
                    'set' => $item->set,
                    'name_en' => $item->item?->exercise?->name_en,
                    'name_ar' => $item->item?->exercise?->name_ar,
                    'day' => $item->item?->day,

                ];
            });

            return [
                'id' => $exerciseUserProgram->id,
                'user_id' => $exerciseUserProgram->user_id,
                'user' => $exerciseUserProgram->user?->fullname(),
                'name' => $exerciseUserProgram->program->name,
                'description' => $exerciseUserProgram->program->description,
                'items' => $items2,
            ];
        });

        return response()->json([
            'result' => $result,
            'totalCount' => $total
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/exercise-users-programs",
     *     summary="ایجاد برنامه ورزشی برای کاربر مشخص",
     *     tags={"UserPrograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="exercise_program_id", type="integer", example=5, description="شناسه برنامه اصلی تمرین"),
     *                 @OA\Property(property="user_id", type="integer", example=12, description="شناسه کاربر هدف")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="برنامه با موفقیت ایجاد شد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Program created successfully"),
     *             @OA\Property(property="program", type="object",
     *                 @OA\Property(property="id", type="integer", example=101),
     *                 @OA\Property(property="exercise_program_id", type="integer", example=5),
     *                 @OA\Property(property="user_id", type="integer", example=12),
     *                 @OA\Property(property="created_at", type="string", example="2025-11-09T15:30:00"),
     *                 @OA\Property(property="updated_at", type="string", example="2025-11-09T15:30:00")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=422, description="داده‌های نامعتبر")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'exercise_program_id' => 'required|integer',
            'user_id' => 'required|integer', // کاربر هدف
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $exerciseProgram = ExerciseProgram::find($request->exercise_program_id);
        // ایجاد رکورد در جدول ExerciseUsersProgram با user_id دلخواه
        $program = ExerciseUsersProgram::create([
            'exercise_program_id' => $request->exercise_program_id,
            'user_id' => $request->user_id,
        ]);

        // گرفتن همه آیتم‌های برنامه اصلی
        $programItems = ExerciseProgramItem::where('exercise_program_id', $request->exercise_program_id)->get();
        $day = 0;
        foreach ($programItems as $item) {
            if($item->day > $day)
            {
                $day = $item->day;
            }

            if($exerciseProgram->is_sick)
            {
                ExerciseUsersProgramItem::create([
                    'exercise_users_program_id' => $program->id,
                    'exercise_program_item_id' => $item->id,
                    'exercise_id' => $item->exercise_id,
                    'set' => $item->set ?: rand(3, 4),
                    'frequency' => $item->frequency ?: rand(12, 14),
                    'user_id' => $request->user_id, // user_id هدف
                ]);
            }
            else
            {

                //if($item->muscle_id > 0)
                {
                    $exerciseMuscle = ExerciseMuscle::where('muscle_id', $item->muscle_id)
                    ->whereHas('exercise', function ($q) use ($exerciseProgram) {

                        if ($exerciseProgram->gender !== 'both') {
                            $q->where(function ($query) use ($exerciseProgram) {
                                $query->where('gender', $exerciseProgram->gender)
                                    ->orWhere('gender', 'both');
                            });
                        }

                        if (!empty($exerciseProgram->location_id)) {
                            $q->whereHas('locations', function ($loc) use ($exerciseProgram) {
                                $loc->where('location_id', $exerciseProgram->location_id);
                            });
                        }
                        if (!empty($exerciseProgram->goal_id)) {
                            $q->whereHas('goals', function ($goal) use ($exerciseProgram) {
                                $goal->where('goal_id', $exerciseProgram->goal_id);
                            });
                        }
                    })
                    ->inRandomOrder()
                    ->first();
                    if (!$exerciseMuscle) {
                        return response()->json([
                            'message' => "هیچ تمرینی برای عضله با شرایط انتخابی پیدا نشد."
                        ], 404);
                    }
                    ExerciseUsersProgramItem::create([
                        'exercise_users_program_id' => $program->id,
                        'exercise_program_item_id' => $item->id,
                        'exercise_id' => $exerciseMuscle->exercise_id,
                        'set' => $item->set ?: rand(3, 4),
                        'frequency' => $item->frequency ?: rand(12, 14),
                        'user_id' => $request->user_id, // user_id هدف
                    ]);
                }
            }
        }
        $program->expire_at = now()->addDays(30);
        $program->save();
        return response()->json([
            'message' => 'Program created successfully',
            'program' => $program
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/exercise-users-programs/{id}",
     *     summary="نمایش جزئیات برنامه ورزشی کاربر",
     *     description="جزئیات یک برنامه ورزشی خاص کاربر به همراه آیتم‌های آن را برمی‌گرداند.",
     *     tags={"ExerciseUsersPrograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه برنامه کاربر",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="نمایش موفق جزئیات برنامه",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=23),
     *             @OA\Property(property="name", type="string", example="برنامه تمرین خانگی"),
     *             @OA\Property(property="description", type="string", example="برنامه برای تقویت عضلات کل بدن"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="user_id", type="integer", example=23),
     *                     @OA\Property(property="frequency", type="integer", example=3),
     *                     @OA\Property(property="set", type="integer", example=4),
     *                     @OA\Property(property="name_en", type="string", example="Chest Exercise"),
     *                     @OA\Property(property="name_ar", type="string", example="تمرين الصدر"),
     *                     @OA\Property(property="home_type", type="string", example="خانگی با دمبل"),
     *                     @OA\Property(property="description_ar", type="string", example="تمرین برای تقویت سینه"),
     *                     @OA\Property(property="imageUrl1", type="string", example="https://example.com/img1.webp", nullable=true),
     *                     @OA\Property(property="imageUrl2", type="string", example="https://example.com/img2.webp", nullable=true),
     *                     @OA\Property(property="video", type="string", example="https://example.com/video.mp4", nullable=true),
     *                     @OA\Property(
     *                         property="goals",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="label", type="string", example="کاهش وزن")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="locations",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="label", type="string", example="باشگاه")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="دسترسی غیرمجاز",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="برنامه یافت نشد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Program not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $program = ExerciseUsersProgram::where('id', $id)->first();

        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }
        $items = $program->items->map(function($item) {
            return [
                'user_id' => $item->user_id,
                'user' => $item->user?->fullname(),
                'frequency' => $item->frequency,
                'set' => $item->set,
                'name_en' => $item->exercise?->name_en,
                'name_ar' => $item->exercise?->name_ar,
                'day' => $item->item?->day,

            ];
        });


        return response()->json([
            'id' => $program->id,
            'user_id' => $program->user_id,
            'user' => $program->user?->fullname(),
            'name' => $program->program->name,
            'description' => $program->program->description,
            'items' => $items,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/exercise-users-programs/{id}",
     *     summary="حذف برنامه ورزشی کاربر",
     *     description="این متد برنامه یک کاربر را با شناسه مشخص حذف می‌کند.",
     *     tags={"ExerciseUsersPrograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه برنامه کاربر برای حذف",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="برنامه با موفقیت حذف شد.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Program deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="دسترسی غیرمجاز",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="برنامه یافت نشد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Program not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $program = ExerciseUsersProgram::where('id', $id)->first();

        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }

        $program->items()->delete();
        $program->delete();

        return response()->json(['message' => 'Program deleted successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/exercise-program-detail",
     *     summary="نمایش جزئیات برنامه ورزشی کاربر",
     *     description="جزئیات یک برنامه ورزشی خاص کاربر به همراه آیتم‌های آن را برمی‌گرداند.",
     *     tags={"ExerciseUsersPrograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه برنامه کاربر",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="نمایش موفق جزئیات برنامه",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=23),
     *             @OA\Property(property="name", type="string", example="برنامه تمرین خانگی"),
     *             @OA\Property(property="description", type="string", example="برنامه برای تقویت عضلات کل بدن"),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="user_id", type="integer", example=23),
     *                     @OA\Property(property="frequency", type="integer", example=3),
     *                     @OA\Property(property="set", type="integer", example=4),
     *                     @OA\Property(property="name_en", type="string", example="Chest Exercise"),
     *                     @OA\Property(property="name_ar", type="string", example="تمرين الصدر"),
     *                     @OA\Property(property="home_type", type="string", example="خانگی با دمبل"),
     *                     @OA\Property(property="description_ar", type="string", example="تمرین برای تقویت سینه"),
     *                     @OA\Property(property="imageUrl1", type="string", example="https://example.com/img1.webp", nullable=true),
     *                     @OA\Property(property="imageUrl2", type="string", example="https://example.com/img2.webp", nullable=true),
     *                     @OA\Property(property="video", type="string", example="https://example.com/video.mp4", nullable=true),
     *                     @OA\Property(
     *                         property="goals",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="label", type="string", example="کاهش وزن")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="locations",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=2),
     *                             @OA\Property(property="label", type="string", example="باشگاه")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="دسترسی غیرمجاز",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="برنامه یافت نشد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Program not found")
     *         )
     *     )
     * )
     */
    public function detail()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $program = ExerciseUsersProgram::where('user_id', $user->id)->orderBy('id', 'desc')
        ->where('expire_at', '>=', date('Y-m-d'))
        ->first();

        if (!$program) {
            return response()->json(['message' => 'Program not found'], 404);
        }
        $items = $program->items->map(function($item) {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'user' => $item->user?->fullname(),
                'frequency' => $item->frequency,
                'set' => $item->set,

                'name_en' => $item->exercise?->name_en,
                'name_ar' => $item->exercise?->name_ar,
                'home_type' => $item->exercise?->home_type,
                'target_muscle' => $item->exercise?->target_muscle,
                'description_ar' => $item->exercise?->description_ar,
                'imageUrl1' => $item->exercise?->image1?->url(),
                'imageUrl2' => $item->exercise?->image2?->url(),
                'video' => $item->exercise?->video,
                'goals' => $item->exercise?->goals->map(fn($t) => [
                    'id' => $t->goal_id,
                    'label' => $t->goal_id ? ExerciseGoals::from($t->goal_id)->label() : null,
                ]),
                'locations' => $item->exercise?->locations->map(fn($t) => [
                    'id' => $t->location_id,
                    'label' => $t->location_id ? ExerciseLocations::from($t->location_id)->label() : null,
                ]),
                'muscles' => $item->exercise?->muscles->map(fn($t) => [
                    'id' => $t->id,
                    'name_ar' => $t->name_ar,
                    'name_en' => $t->name_en
                ]),
                'day' => $item->item?->day,

            ];
        });


        return response()->json([
            'id' => $program->id,
            'created_at' => $program->created_at,
            'expire_at' => $program->expire_at,
            'user_id' => $program->user_id,
            'user' => $program->user?->fullname(),
            'name' => $program->program?->name,
            'description' => $program->program?->description,
            'location_id' => $program->program?->location_id,
            'location' => $program->program?->location_id ? ExerciseLocations::from($program->program?->location_id)->label() : null,
            'items' => $items,
        ]);
        //return response()->json($program);
    }

    /**
     * جستجوی تمرین‌های جایگزین برای یک آیتم برنامه ورزشی کاربر (همان عضله، جنسیت، مکان و هدف).
     */
    private function alternativesQuery(ExerciseUsersProgramItem $item, $muscleOverride = null, bool $anyMuscle = false)
    {
        $program = $item->program;
        $base = $program?->program;
        $muscleId = $item->item?->muscle_id;
        $currentIds = ExerciseUsersProgramItem::where('exercise_users_program_id', $item->exercise_users_program_id)
            ->pluck('exercise_id')->all();

        $query = \App\Models\Exercise::query()->whereNotIn('id', $currentIds);

        if ($anyMuscle) {
            // بدون محدودیت عضله (برای اعتبارسنجی جایگزینی از هر دسته)
        } elseif ($muscleOverride) {
            $query->whereHas('muscles', fn ($q) => $q->where('muscle.id', $muscleOverride));
        } elseif ($muscleId) {
            $query->whereHas('muscles', fn ($q) => $q->where('muscle.id', $muscleId));
        } else {
            $muscleIds = $item->exercise?->muscles->pluck('id')->all() ?? [];
            if (empty($muscleIds)) {
                return $query->whereRaw('1 = 0');
            }
            $query->whereHas('muscles', fn ($q) => $q->whereIn('muscle.id', $muscleIds));
        }

        if ($base) {
            if ($base->gender && $base->gender !== 'both') {
                $query->whereIn('gender', [$base->gender, 'both']);
            }
            if (!empty($base->location_id)) {
                $query->whereHas('locations', fn ($q) => $q->where('location_id', $base->location_id));
            }
            if (!empty($base->goal_id)) {
                $query->whereHas('goals', fn ($q) => $q->where('goal_id', $base->goal_id));
            }
        }

        return $query;
    }

    /**
     * @OA\Get(
     *     path="/api/exercise-users-program-items/{id}/alternatives",
     *     summary="تمرین‌های جایگزین برای یک حرکت برنامه ورزشی کاربر",
     *     tags={"ExerciseUsersPrograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="شناسه آیتم برنامه کاربر (items.id در exercise-program-detail)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="q", in="query", required=false, description="جستجو در نام تمرین (عربی/انگلیسی/فارسی)", @OA\Schema(type="string")),
     *     @OA\Parameter(name="muscle_id", in="query", required=false, description="فیلتر دسته (عضله)؛ پیش‌فرض عضله‌ی خود حرکت", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="items + categories (دسته‌ها) + default_muscle_id"),
     *     @OA\Response(response=404, description="آیتم یافت نشد")
     * )
     */
    public function alternatives(Request $request, $id)
    {
        $user = Auth::user();
        $item = ExerciseUsersProgramItem::where('id', $id)->where('user_id', $user->id)->first();
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $defaultMuscleId = $item->item?->muscle_id ?: $item->exercise?->muscles->first()?->id;
        // با جستجو و بدون انتخاب دسته، در همه‌ی دسته‌ها جستجو می‌شود
        $muscleId = $request->filled('muscle_id')
            ? (int) $request->muscle_id
            : ($request->filled('q') ? null : $defaultMuscleId);

        $query = $this->alternativesQuery($item, $muscleId, $muscleId === null);
        if ($request->filled('q')) {
            $term = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($request->q)) . '%';
            $query->where(fn ($q) => $q->where('name_ar', 'like', $term)
                ->orWhere('name_en', 'like', $term)
                ->orWhere('name_fa', 'like', $term));
        }

        $exercises = $query
            ->with(['image1', 'image2'])
            ->orderBy('name_ar')
            ->limit(100)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name_en' => $e->name_en,
                'name_ar' => $e->name_ar,
                'imageUrl1' => $e->image1?->url(),
                'imageUrl2' => $e->image2?->url(),
            ]);

        // دسته‌ها = عضله‌هایی که تمرین مناسب (جنسیت/مکان/هدف) دارند
        $categories = \App\Models\Muscle::query()
            ->whereIn('id', function ($sub) use ($item) {
                $ids = $this->alternativesQuery($item, null, true)->pluck('id');
                $sub->select('muscle_id')->from('exercise_muscle')->whereIn('exercise_id', $ids);
            })
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en'])
            ->map(fn ($m) => ['id' => $m->id, 'name_ar' => $m->name_ar, 'name_en' => $m->name_en]);

        return response()->json([
            'items' => $exercises,
            'categories' => $categories,
            'default_muscle_id' => $defaultMuscleId,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/exercise-users-program-items/{id}/replace",
     *     summary="جایگزین کردن یک حرکت برنامه ورزشی کاربر با تمرین دیگر",
     *     tags={"ExerciseUsersPrograms"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="exercise_id", type="integer", example=12, description="شناسه تمرین جدید (از alternatives)")
     *     )),
     *     @OA\Response(response=200, description="حرکت جایگزین شد"),
     *     @OA\Response(response=404, description="آیتم یافت نشد"),
     *     @OA\Response(response=422, description="تمرین جایگزین نامعتبر")
     * )
     */
    public function replace(Request $request, $id)
    {
        $user = Auth::user();
        $item = ExerciseUsersProgramItem::where('id', $id)->where('user_id', $user->id)->first();
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'exercise_id' => 'required|integer|exists:exercises,id',
            'set' => 'nullable|integer|min:1|max:20',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $allowed = $this->alternativesQuery($item, null, true)->where('id', $request->exercise_id)->exists();
        if (!$allowed) {
            return response()->json(['message' => 'هذا التمرين غير مناسب كبديل'], 422);
        }

        $item->exercise_id = $request->exercise_id;
        if ($request->filled('set')) {
            $item->set = (int) $request->set;
        }
        $item->save();

        return response()->json(['message' => 'Exercise replaced successfully']);
    }
}
