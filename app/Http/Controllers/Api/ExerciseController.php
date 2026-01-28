<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Models\ExerciseGoal;
use App\Models\ExerciseLocation;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Enums\ExerciseGoals;
use App\Enums\ExerciseLocations;
use App\Models\Image;
use App\Models\Muscle;
use App\Models\ExerciseMuscle;
use Illuminate\Support\Str;

class ExerciseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/exercises",
     *     summary="دریافت لیست حرکات ورزشی",
     *     description="برمی‌گرداند لیست حرکات ورزشی با فیلتر بر اساس نام، اهداف، مکان‌ها و عضلات",
     *     tags={"Exercises"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="جستجو در نام حرکت (عربی، انگلیسی، فارسی)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="exercise_goals",
     *         in="query",
     *         description="آیدی اهداف (لیست جداشده با کاما)",
     *         required=false,
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="جنسیت کاربر",
     *         @OA\Schema(type="string", enum={"male","female","both"})
     *     ),
     *     @OA\Parameter(
     *         name="exercise_locations",
     *         in="query",
     *         description="آیدی مکان‌ها (لیست جداشده با کاما)",
     *         required=false,
     *         @OA\Schema(type="string", example="1,5,8")
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم‌ها در هر صفحه",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="لیست حرکات با موفقیت دریافت شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="gender", type="string", example="male"),
     *                     @OA\Property(property="name_ar", type="string"),
     *                     @OA\Property(property="name_en", type="string"),
     *                     @OA\Property(property="name_fa", type="string"),
     *                     @OA\Property(property="home_type", type="string"),
     *                     @OA\Property(property="target_muscle", type="string"),
     *                     @OA\Property(property="description_ar", type="string"),
     *                     @OA\Property(property="imageUrl1", type="string", nullable=true),
     *                     @OA\Property(property="imageUrl2", type="string", nullable=true),
     *                     @OA\Property(property="video", type="string", nullable=true),
     *                     @OA\Property(property="goals", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="locations", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="muscles", type="array", @OA\Items(type="object"))
     *                 )
     *             ),
     *             @OA\Property(property="totalCount", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }

        $pageSize = (int)($request->pagesize ?? 20);
        $query = Exercise::with(['goals', 'locations', 'muscles', 'image1', 'image2']);

        if ($request->filled('name')) {
            $name = $request->name;
            $query->where(function ($q) use ($name) {
                $q->where('name_ar', 'like', "%{$name}%")
                ->orWhere('name_en', 'like', "%{$name}%")
                ->orWhere('name_fa', 'like', "%{$name}%");
            });
        }

        if ($request->filled('exercise_goals')) {
            $exerciseGoals = is_array($request->exercise_goals)
                ? $request->exercise_goals
                : explode(',', $request->exercise_goals);

            $query->whereHas('goals', fn($q) => $q->whereIn('goal_id', $exerciseGoals));
        }

        if ($request->filled('exercise_locations')) {
            $exerciseLocations = is_array($request->exercise_locations)
                ? $request->exercise_locations
                : explode(',', $request->exercise_locations);

            $query->whereHas('locations', fn($q) => $q->whereIn('location_id', $exerciseLocations));
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

        $exercises = $query->orderByDesc('id')->paginate($pageSize);

        $result = $exercises->getCollection()->transform(fn($exercise) => [
            'id' => $exercise->id,
            'gender' => $exercise->gender,
            'name_ar' => $exercise->name_ar,
            'name_en' => $exercise->name_en,
            'name_fa' => $exercise->name_fa,
            'home_type' => $exercise->home_type,
            'target_muscle' => $exercise->target_muscle,
            'description_ar' => $exercise->description_ar,
            'imageUrl1' => $exercise->image1?->url(),
            'imageUrl2' => $exercise->image2?->url(),
            'video' => $exercise->video,
            'goals' => $exercise->goals->map(fn($t) => [
                'id' => $t->goal_id,
                'label' => $t->goal_id ? ExerciseGoals::from($t->goal_id)->label() : null,
            ]),
            'locations' => $exercise->locations->map(fn($t) => [
                'id' => $t->location_id,
                'label' => $t->location_id ? ExerciseLocations::from($t->location_id)->label() : null,
            ]),
            'muscles' => $exercise->muscles->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'name_en' => $m->name_en,
                'name_ar' => $m->name_ar,
            ]),
        ]);

        return response()->json([
            'result' => $result,
            'totalCount' => $exercises->total(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/exercises",
     *     summary="ایجاد تمرین جدید",
     *     tags={"Exercises"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="name_ar", type="string", example="تمرين الصدر"),
     *                 @OA\Property(property="name_en", type="string", example="Chest Exercise"),
     *                 @OA\Property(property="name_fa", type="string", example="تمرین سینه"),
     *                 @OA\Property(property="home_type", type="string", example="خانگی با دمبل"),
     *                 @OA\Property(property="target_muscle", type="string", example="سینه"),
     *                 @OA\Property(property="description_ar", type="string", example="تمرين لتقوية عضلات الصدر"),
     *                 @OA\Property(property="image1", type="string", format="binary"),
     *                 @OA\Property(property="image2", type="string", format="binary"),
     *                 @OA\Property(property="video", type="string"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female" , "both"}, example="male"),
     *                 @OA\Property(
     *                     property="goals[]",
     *                     description="آرایه شناسه‌های اهداف تمرین",
     *                     type="array",
     *                     @OA\Items(type="integer", example=2)
     *                 ),
     *                 @OA\Property(
     *                     property="locations[]",
     *                     description="آرایه شناسه‌های موقعیت تمرین",
     *                     type="array",
     *                     @OA\Items(type="integer", example=2)
     *                 ),
     *                 @OA\Property(
     *                     property="muscles[]",
     *                     description="آرایه شناسه‌های عضلات مرتبط با تمرین",
     *                     type="array",
     *                     @OA\Items(type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="تمرین با موفقیت ایجاد شد"),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=422, description="داده‌های نامعتبر")
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
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_fa' => 'nullable|string|max:255',
            'image1' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'image2' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'video' => 'nullable|string',
            'goals' => 'nullable|array',
            'locations' => 'nullable|array',
            'muscles' => 'nullable|array',
            'muscles.*' => 'integer|exists:muscle,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadPath = public_path('uploads/images/' . date('Y/m'));
        if (!file_exists($uploadPath)) mkdir($uploadPath, 0775, true);

        // تابع کمکی برای آپلود فایل و ذخیره در جدول images
        $saveFile = function ($file) use ($uploadPath, $user) {
            $token = Str::random(32);
            $extension = $file->getClientOriginalExtension();
            $filename = $token . '.' . $extension;
            $file->move($uploadPath, $filename);

            $url = $filename;

            return Image::create([
                'user_id'   => $user->id,
                'token'     => $token,
                'extension' => $extension,
                'url'       => $url,
                'dimension' => @json_encode(getimagesize(public_path($url)) ?: []),
                'month'     => date('m'),
                'year'      => date('Y'),
            ])->id;
        };

        $imageId1 = $request->hasFile('image1') ? $saveFile($request->file('image1')) : null;

        $imageId2 = $request->hasFile('image2') ? $saveFile($request->file('image2')) : null;


        $exercise = Exercise::create([
            'gender' => $request->gender,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'name_fa' => $request->name_fa,
            'home_type' => $request->home_type,
            'target_muscle' => $request->target_muscle,
            'description_ar' => $request->description_ar,
            'image_id1' => $imageId1,
            'image_id2' => $imageId2,
            'video' => $request->video,
        ]);


        foreach ($request->goals ?? [] as $goalId) {

            ExerciseGoal::create([
                'exercise_id' => $exercise->id,
                'goal_id' => $goalId
            ]);

        }

        foreach ($request->locations ?? [] as $locationId) {
            ExerciseLocation::create([
                'exercise_id' => $exercise->id,
                'location_id' => $locationId
            ]);
        }

        if ($request->has('muscles')) {
            $exercise->muscles()->sync($request->muscles);
        }

        return response()->json(['message' => 'تمرین با موفقیت ایجاد شد.'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/exercises/{id}",
     *     summary="ویرایش تمرین",
     *     tags={"Exercises"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه تمرین",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(property="name_ar", type="string", example="تمرين البطن"),
     *                 @OA\Property(property="name_en", type="string", example="Abs Exercise"),
     *                 @OA\Property(property="name_fa", type="string", example="تمرین شکم"),
     *                 @OA\Property(property="home_type", type="string", example="خانگی بدون وسیله"),
     *                 @OA\Property(property="target_muscle", type="string", example="شکم"),
     *                 @OA\Property(property="description_ar", type="string", example="تمرين لتقوية عضلات البطن"),
     *                 @OA\Property(property="image1", type="string", format="binary"),
     *                 @OA\Property(property="image2", type="string", format="binary"),
     *                 @OA\Property(property="video", type="string"),
     *                 @OA\Property(property="del_image1", type="boolean", example=false, description="اگر true باشد تصویر اول حذف می‌شود"),
     *                 @OA\Property(property="del_image2", type="boolean", example=false, description="اگر true باشد تصویر دوم حذف می‌شود"),
     *                 @OA\Property(property="gender", type="string", enum={"male", "female" , "both"}, example="male"),
     *                 @OA\Property(
     *                     property="goals[]",
     *                     description="آرایه شناسه‌های اهداف تمرین",
     *                     type="array",
     *                     @OA\Items(type="integer", example=2)
     *                 ),
     *                 @OA\Property(
     *                     property="locations[]",
     *                     description="آرایه شناسه‌های موقعیت تمرین",
     *                     type="array",
     *                     @OA\Items(type="integer", example=2)
     *                 ),
     *                 @OA\Property(
     *                     property="muscles[]",
     *                     description="آرایه شناسه‌های عضلات مرتبط با تمرین",
     *                     type="array",
     *                     @OA\Items(type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="تمرین با موفقیت بروزرسانی شد"),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="تمرین یافت نشد"),
     *     @OA\Response(response=422, description="داده‌های نامعتبر")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $exercise = Exercise::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'gender' => 'sometimes|in:male,female,both',
            'name_ar' => 'nullable|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'name_fa' => 'nullable|string|max:255',
            'image1' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'image2' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'video'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $uploadPath = public_path('uploads/images/' . date('Y/m'));
        if (!file_exists($uploadPath)) mkdir($uploadPath, 0775, true);

        // تابع ذخیره فایل جدید
        $saveFile = function ($file) use ($uploadPath, $user) {
            $token = Str::random(32);
            $extension = $file->getClientOriginalExtension();
            $filename = $token . '.' . $extension;
            $file->move($uploadPath, $filename);
            $url = $filename;

            return Image::create([
                'user_id'   => $user->id,
                'token'     => $token,
                'extension' => $extension,
                'url'       => $url,
                'dimension' => @json_encode(getimagesize(public_path($url)) ?: []),
                'month'     => date('m'),
                'year'      => date('Y'),
            ])->id;
        };

        // نگه‌داری فایل‌های قبلی
        $imageId1 = $exercise->image_id1;
        $imageId2 = $exercise->image_id2;

        // جایگزینی فایل‌های جدید
        if ($request->hasFile('image1')) $imageId1 = $saveFile($request->file('image1'));
        if ($request->hasFile('image2')) $imageId2 = $saveFile($request->file('image2'));

        if ($request->del_image1 == true) $imageId1 = null;
        if ($request->del_image2 == true) $imageId2 = null;

        // به‌روزرسانی تمرین
        $exercise->update([
            'gender' => $request->gender,
            'name_ar' => $request->name_ar,
            'name_en' => $request->name_en,
            'name_fa' => $request->name_fa,
            'home_type' => $request->home_type,
            'target_muscle' => $request->target_muscle,
            'description_ar' => $request->description_ar,
            'image_id1' => $imageId1,
            'image_id2' => $imageId2,
            'video' => $request->video,
        ]);

        ExerciseGoal::where('exercise_id', $exercise->id)->delete();
        foreach ($request->goals ?? [] as $goalId) {
            ExerciseGoal::create([
                'exercise_id' => $exercise->id,
                'goal_id' => $goalId
            ]);
        }

        ExerciseLocation::where('exercise_id', $exercise->id)->delete();
        foreach ($request->locations ?? [] as $locationId) {
            ExerciseLocation::create([
                'exercise_id' => $exercise->id,
                'location_id' => $locationId
            ]);
        }

        ExerciseMuscle::where('exercise_id', $exercise->id)->delete();
        foreach ($request->muscles ?? [] as $muscleId) {
            ExerciseMuscle::insert([
                'exercise_id' => $exercise->id,
                'muscle_id' => $muscleId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        return response()->json(['status' => 'success', 'data' => $exercise], 200);
    }


    /**
     * @OA\Delete(
     *     path="/api/exercises/{id}",
     *     summary="حذف تمرین",
     *     tags={"Exercises"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه تمرین برای حذف",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="تمرین با موفقیت حذف شد."),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="تمرین یافت نشد")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $exercise = Exercise::findOrFail($id);

        // حذف فایل‌های مرتبط (اگر وجود دارند)
        $deleteFile = function ($fileId) {
            if (!$fileId) return;
            $file = Image::find($fileId);
            if ($file && $file->url) {
                $path = public_path($file->url);
                if (file_exists($path)) {
                    @unlink($path);
                }
                $file->delete();
            }
        };

        $deleteFile($exercise->image_id1);
        $deleteFile($exercise->image_id2);


        // حذف ارتباط‌ها
        $exercise->goals()->delete();
        $exercise->locations()->delete();
        ExerciseLocation::where('exercise_id', $exercise->id)->delete();
        // حذف رکورد تمرین
        $exercise->delete();

        return response()->json(['message' => 'تمرین با موفقیت حذف شد.'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/exercises/{id}",
     *     summary="نمایش جزئیات تمرین ورزشی",
     *     description="این متد جزئیات یک تمرین خاص را با شناسه آن برمی‌گرداند.",
     *     operationId="showExercise",
     *     tags={"Exercises"},
     *
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه تمرین",
     *         required=true,
     *         @OA\Schema(type="integer", example=1196)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="نمایش موفق جزئیات تمرین",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1196),
     *             @OA\Property(property="name_ar", type="string", example="تمرين البطن"),
     *             @OA\Property(property="name_en", type="string", example="Abs Exercise"),
     *             @OA\Property(property="name_fa", type="string", example="تمرین شکم"),
     *             @OA\Property(property="home_type", type="string", example="خانگی بدون وسیله"),
     *             @OA\Property(property="target_muscle", type="string", example="شکم"),
     *             @OA\Property(property="description_ar", type="string", example="تمرين لتقوية عضلات البطن"),
     *             @OA\Property(property="imageUrl1", type="string", example="https://api.di3t-club.com/uploads/images/2025/11/img1.webp"),
     *             @OA\Property(property="imageUrl2", type="string", example="https://api.di3t-club.com/uploads/images/2025/11/img2.webp"),
     *             @OA\Property(property="video", type="string", example="https://api.di3t-club.com/uploads/videos/2025/11/video.mp4"),
     *             @OA\Property(
     *                 property="goals",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="کاهش وزن")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="locations",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="label", type="string", example="باشگاه")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="muscles",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="شکم"),
     *                     @OA\Property(property="name_en", type="string", example="Abs"),
     *                     @OA\Property(property="name_ar", type="string", example="عضلات البطن")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="دسترسی غیرمجاز.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="تمرین یافت نشد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="برنامه یافت نشد.")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }

        $exercise = Exercise::with(['goals', 'locations', 'muscles'])->findOrFail($id);

        return response()->json([
            'id' => $exercise->id,
            'gender' => $exercise->gender,
            'name_ar' => $exercise->name_ar,
            'name_en' => $exercise->name_en,
            'name_fa' => $exercise->name_fa,
            'home_type' => $exercise->home_type,
            'target_muscle' => $exercise->target_muscle,
            'description_ar' => $exercise->description_ar,
            'imageUrl1' => $exercise->image1?->url(),
            'imageUrl2' => $exercise->image2?->url(),
            'video' => $exercise->video,
            'goals' => $exercise->goals->map(fn($t) => [
                'id' => $t->goal_id,
                'label' => $t->goal_id ? ExerciseGoals::from($t->goal_id)->label() : null,
            ]),
            'locations' => $exercise->locations->map(fn($t) => [
                'id' => $t->location_id,
                'label' => $t->location_id ? ExerciseLocations::from($t->location_id)->label() : null,
            ]),
            'muscles' => $exercise->muscles->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'name_en' => $m->name_en,
                'name_ar' => $m->name_ar,
            ]),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/muscles",
     *     summary="دریافت لیست عضلات",
     *     description="این متد لیست کامل عضلات را برمی‌گرداند. برای استفاده نیاز به احراز هویت دارد.",
     *     tags={"Exercises"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست عضلات با موفقیت دریافت شد",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     )
     * )
     */
    public function muscleLists()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 401);
        }

        return response()->json(Muscle::get());
    }

}
