<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\DailyActivityLevel;
use App\Enums\DietGoal;
use App\Enums\HasDietHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Enums\FoodCulture;
use Carbon\Carbon;
use OpenApi\Annotations as OA;
use App\Enums\DietType;
use App\Enums\ExerciseLocations;
use App\Enums\MealType;
use App\Enums\FoodType;
use App\Models\ExerciseUsersProgram;
use Illuminate\Support\Str;
use App\Services\OpenAIService;
use Illuminate\Http\JsonResponse;

class DietUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Post(
     *     path="/api/ai/generate",
     *     operationId="generateAiContent",
     *     tags={"AI"},

     *     summary="تولید متن با هوش مصنوعی",
     *     description="این API با استفاده از OpenAI متن تبلیغاتی فارسی تولید می‌کند.",
     *
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             example={}
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="پاسخ موفق",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     example="این یک متن تبلیغاتی حرفه‌ای برای معرفی خدمات شماست..."
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="خطای سرور"
     *     )
     * )
     */

    public function generate(OpenAIService $openAI): JsonResponse
    {
        $result = $openAI->chat([
            ['role' => 'system', 'content' => 'You are a Persian assistant'],
            ['role' => 'user', 'content' => 'یک متن تبلیغاتی بنویس']
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'content' => $result
            ]
        ]);
    }

    function getQuery($item){
        $query = str_replace(array('?'), array('\'%s\''), $item->toSql());
        return $query = vsprintf($query, $item->getBindings());
            //echo($query);
    }

    /**
     * @OA\Post(
     *     path="/api/diet/register",
     *     summary="ثبت‌نام کاربر برای دریافت برنامه رژیم غذایی",
     *     description="ثبت‌نام کاربر توسط ادمین و امکان اختصاص نقش‌ها",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"gender","phone","password"},
     *             @OA\Property(property="gender", type="string", enum={"male","female"}, example="male", description="جنسیت کاربر"),
     *             @OA\Property(property="first_name", type="string", example="علی"),
     *             @OA\Property(property="last_name", type="string", example="رضایی"),
     *             @OA\Property(property="food_culture", type="integer", example=1),
     *             @OA\Property(property="phone", type="string", example="09121234567"),
     *             @OA\Property(property="email", type="string", format="email", example="ali@example.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="height", type="integer", example=175),
     *             @OA\Property(property="weight", type="float", example=70.5),
     *             @OA\Property(property="target_weight", type="float", example=68.0),
     *             @OA\Property(property="wrist_size", type="integer", example=18),
     *             @OA\Property(property="pregnancy_week", type="integer", example=0),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="state_id", type="integer", example=10),
     *             @OA\Property(property="city_id", type="integer", example=100),
     *             @OA\Property(property="postal_code", type="string", example="1234567890"),
     *             @OA\Property(property="address", type="string", example="تهران، خیابان ولیعصر"),
     *             @OA\Property(property="diet_type_id", type="integer", example=2),
     *             @OA\Property(property="food_type_id", type="integer", example=3),
     *             @OA\Property(property="daily_activity_level", type="integer", example=2),
     *             @OA\Property(property="diet_goal", type="integer", example=1),
     *             @OA\Property(property="has_diet_history", type="integer", example=0),
     *             @OA\Property(property="diet_history", type="string", example=""),
     *             @OA\Property(property="package", type="integer", example=1),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 description="آرایه‌ای از شناسه نقش‌ها برای اختصاص به کاربر",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="ثبت‌نام با موفقیت انجام شد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="ثبت‌نام با موفقیت انجام شد"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="first_name", type="string", example="علی"),
     *                 @OA\Property(property="last_name", type="string", example="رضایی"),
     *                 @OA\Property(property="phone", type="string", example="09121234567"),
     *                 @OA\Property(property="email", type="string", example="ali@example.com"),
     *                 @OA\Property(property="roles", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="admin")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی")
     * )
     */

    public function register(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'gender' => 'required|in:male,female',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'food_culture' => 'nullable|integer',
            'phone' => 'required|string|unique:diet_users,phone',
            'email' => 'nullable|email|unique:diet_users,email',
            'password' => 'required|string|min:6',
            'birth_date' => 'nullable|date',
            'height' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'target_weight' => 'nullable|numeric',
            'wrist_size' => 'nullable|integer',
            'pregnancy_week' => 'nullable|integer',
            'country_id' => 'nullable|integer',
            'state_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'postal_code' => 'nullable|string',
            'address' => 'nullable|string',
            'diet_type_id' => 'nullable|integer',
            'food_type_id' => 'nullable|integer',
            'daily_activity_level' => 'nullable|integer',
            'diet_goal' => 'nullable|integer',
            'has_diet_history' => 'nullable|integer',
            'diet_history' => 'nullable|int',
            'package' => 'nullable|integer',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        $user = User::find($user->id);
        if (!$user) {
            return response()->json(['message' => 'کاربر یافت نشد'], 404);
        }

        if ($request->has('roles')) {
            // اگر roles ارسال شده ولی خالی باشد => حذف نقش‌ها
            if (empty($request->roles)) {
                $user->syncRoles([]);
            } else {
                $roleNames = \Spatie\Permission\Models\Role::whereIn('id', $request->roles)
                    ->where('guard_name', 'api')
                    ->pluck('name')
                    ->toArray();

                if (empty($roleNames)) {
                    return response()->json([
                        'message' => "نقش‌های انتخاب‌شده برای guard 'api' وجود ندارند"
                    ], 400);
                }

                $user->syncRoles($roleNames);
            }
        }

        return response()->json([
            'message' => 'ثبت‌نام با موفقیت انجام شد',
            'user' => $user->load('roles')
        ], 201);
    }


    /**
     * @OA\Put(
     *     path="/api/diet/{id}/update",
     *     summary="ویرایش اطلاعات کاربر رژیم",
     *     description="ویرایش اطلاعات کامل یک کاربر رژیم. فقط توسط مدیر کل قابل انجام است.",
     *     operationId="updateDietUser",
     *     tags={"Diet Users"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه کاربر رژیم",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}),
     *             @OA\Property(property="first_name", type="string", maxLength=100),
     *             @OA\Property(property="last_name", type="string", maxLength=100),
     *             @OA\Property(property="food_culture", type="integer"),
     *             @OA\Property(property="phone", type="string", example="09121234567"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=6),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="height", type="integer"),
     *             @OA\Property(property="weight", type="float"),
     *             @OA\Property(property="target_weight", type="float"),
     *             @OA\Property(property="wrist_size", type="integer"),
     *             @OA\Property(property="pregnancy_week", type="integer"),
     *             @OA\Property(property="country_id", type="integer"),
     *             @OA\Property(property="state_id", type="integer"),
     *             @OA\Property(property="city_id", type="integer"),
     *             @OA\Property(property="postal_code", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="diet_type_id", type="integer"),
     *             @OA\Property(property="food_type_id", type="integer"),
     *             @OA\Property(property="daily_activity_level", type="integer"),
     *             @OA\Property(property="diet_goal", type="integer"),
     *             @OA\Property(property="has_diet_history", type="integer", enum={0,1}),
     *             @OA\Property(property="diet_history", type="string"),
     *             @OA\Property(property="package", type="integer"),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 description="آرایه‌ای از شناسه نقش‌ها برای اختصاص به کاربر",
     *                 @OA\Items(type="integer", example=1)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="اطلاعات با موفقیت ویرایش شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="اطلاعات با موفقیت ویرایش شد"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="درخواست نامعتبر (خطا در اعتبارسنجی)"
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'gender' => 'sometimes|in:male,female',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'food_culture' => 'nullable|integer',
            'phone' => 'sometimes|string|unique:diet_users,phone,' . $user->id,
            'email' => 'nullable|email|unique:diet_users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'birth_date' => 'nullable|date',
            'height' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'target_weight' => 'nullable|numeric',
            'wrist_size' => 'nullable|integer',
            'pregnancy_week' => 'nullable|integer',
            'country_id' => 'nullable|integer',
            'state_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'postal_code' => 'nullable|string',
            'address' => 'nullable|string',
            'diet_type_id' => 'nullable|integer',
            'food_type_id' => 'nullable|integer',
            'daily_activity_level' => 'nullable|integer',
            'diet_goal' => 'nullable|integer',
            'has_diet_history' => 'nullable|integer',
            'diet_history' => 'nullable|string',
            'package' => 'nullable|integer',
            'roles' => 'nullable|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        if ($request->has('roles')) {
            // اگر roles ارسال شده ولی خالی باشد => حذف نقش‌ها
            if (empty($request->roles)) {
                $user->syncRoles([]);
            } else {
                $roleNames = \Spatie\Permission\Models\Role::whereIn('id', $request->roles)
                    ->where('guard_name', 'api')
                    ->pluck('name')
                    ->toArray();

                if (empty($roleNames)) {
                    return response()->json([
                        'message' => "نقش‌های انتخاب‌شده برای guard 'api' وجود ندارند"
                    ], 400);
                }

                $user->syncRoles($roleNames);
            }
        }

        return response()->json(['message' => 'اطلاعات با موفقیت ویرایش شد', 'user' => $user]);
    }


    /**
     * @OA\Post(
     *     path="/api/diet-users/set-roles",
     *     summary="تنظیم نقش‌های جدید برای یک کاربر رژیم غذایی",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "roles"},
     *             @OA\Property(property="user_id", type="integer", example=12, description="شناسه کاربر"),
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 description="آرایه‌ای از شناسه‌های نقش‌ها (role_id)",
     *                 @OA\Items(type="integer", example=2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="نقش‌های کاربر با موفقیت به‌روزرسانی شدند",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="نقش‌های کاربر با موفقیت بروزرسانی شدند"),
     *             @OA\Property(property="user_id", type="integer", example=12),
     *             @OA\Property(property="roles", type="array", @OA\Items(type="string", example="expert"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="کاربر یافت نشد"
     *     )
     * )
     */
    public function setRoles(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $request->validate([
            'user_id' => 'required|integer|exists:diet_users,id',
            'roles' => 'required|array',
            'roles.*' => 'integer|exists:roles,id',
            'guard' => 'nullable|string|in:web,api', // اضافه شد
        ]);

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json(['message' => 'کاربر یافت نشد'], 404);
        }

        // استفاده از guard ارسالی یا پیش‌فرض api
        $guard = $request->guard ?? 'api';

        // گرفتن نام نقش‌ها با guard مناسب
        $roleNames = \Spatie\Permission\Models\Role::whereIn('id', $request->roles)
            ->where('guard_name', $guard)
            ->pluck('name')
            ->toArray();

        if (empty($roleNames)) {
            return response()->json([
                'message' => "نقش‌های انتخاب‌شده برای guard '$guard' وجود ندارند"
            ], 400);
        }

        // جایگزینی نقش‌ها
        $user->syncRoles($roleNames);

        return response()->json([
            'message' => 'نقش‌های کاربر با موفقیت بروزرسانی شدند',
            'user_id' => $user->id,
            'roles' => $roleNames,
            'guard' => $guard,
        ]);
    }


    /**
     * @OA\Put(
     *     path="/api/update-user",
     *     summary="ویرایش اطلاعات کاربر رژیم غذایی",
     *     description="این متد فقط توسط سوپریوزر قابل استفاده است و اطلاعات کاربر را ویرایش می‌کند.",
     *     tags={"Diet Users"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male"),
     *             @OA\Property(property="first_name", type="string", example="Hossein"),
     *             @OA\Property(property="last_name", type="string", example="Golab"),
     *             @OA\Property(property="food_culture", type="integer", example=1),
     *             @OA\Property(property="phone", type="string", example="09121234567"),
     *             @OA\Property(property="email", type="string", example="test@example.com"),
     *             @OA\Property(property="password", type="string", example="123456"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1985-08-11"),
     *             @OA\Property(property="height", type="integer", example=175),
     *             @OA\Property(property="weight", type="float", example=80),
     *             @OA\Property(property="target_weight", type="float", example=80),
     *             @OA\Property(property="wrist_size", type="integer", example=18),
     *             @OA\Property(property="pregnancy_week", type="integer", example=20),
     *             @OA\Property(property="country_id", type="integer", example=1),
     *             @OA\Property(property="state_id", type="integer", example=10),
     *             @OA\Property(property="city_id", type="integer", example=100),
     *             @OA\Property(property="postal_code", type="string", example="1234567890"),
     *             @OA\Property(property="address", type="string", example="Tehran, Valiasr St."),
     *             @OA\Property(property="diet_type_id", type="integer", example=2),
     *             @OA\Property(property="food_type_id", type="integer", example=2),
     *             @OA\Property(property="daily_activity_level", type="integer", example=3),
     *             @OA\Property(property="diet_goal", type="integer", example=1),
     *             @OA\Property(property="has_diet_history", type="integer", example=1),
     *             @OA\Property(property="diet_history", type="string", example="Previously followed a keto diet"),
     *             @OA\Property(property="package", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="اطلاعات با موفقیت ویرایش شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="اطلاعات با موفقیت ویرایش شد"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی"
     *     )
     * )
     */

    public function updateUser(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $user = User::find($user->id);
        if($user == null)
        {
            return response()->json([],200);
        }

        $validated = $request->validate([
            'gender' => 'sometimes|in:male,female',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'food_culture' => 'nullable|integer',
            'phone' => 'sometimes|string|unique:diet_users,phone,' . $user->id,
            'email' => 'nullable|email|unique:diet_users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'birth_date' => 'nullable|date',
            'height' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'target_weight' => 'nullable|numeric',
            'wrist_size' => 'nullable|integer',
            'pregnancy_week' => 'nullable|integer',
            'country_id' => 'nullable|integer',
            'state_id' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'postal_code' => 'nullable|string',
            'address' => 'nullable|string',
            'diet_type_id' => 'nullable|integer',
            'food_type_id' => 'nullable|integer',
            'daily_activity_level' => 'nullable|integer',
            'diet_goal' => 'nullable|integer',
            'has_diet_history' => 'nullable|integer',
            'diet_history' => 'nullable|string',
            'package' => 'nullable|integer',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json(['message' => 'اطلاعات با موفقیت ویرایش شد', 'user' => $user]);
    }


    /**
     * @OA\Post(
     *     path="/api/diet-users",
     *     summary="دریافت لیست کاربران رژیم غذایی با امکان جستجو و فیلتر (شامل فیلتر براساس نقش و برنامه هفتگی)",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم‌ها در هر صفحه",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="شماره صفحه",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="جستجو بر اساس نام یا نام خانوادگی",
     *         @OA\Schema(type="string", example="علی")
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="جستجو بر اساس شماره موبایل",
     *         @OA\Schema(type="string", example="09121234567")
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="جنسیت کاربر",
     *         @OA\Schema(type="string", enum={"male","female"})
     *     ),
     *     @OA\Parameter(
     *         name="age",
     *         in="query",
     *         description="سن کاربر",
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Parameter(
     *         name="expire_days_from",
     *         in="query",
     *         description="تعداد روزهای مانده تا انقضای اشتراک (کمینه برای فیلتر)",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="expire_days_to",
     *         in="query",
     *         description="تعداد روزهای مانده تا انقضای اشتراک (بیشینه برای فیلتر)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="role",
     *         in="query",
     *         description="شناسه نقش برای فیلتر کاربران",
     *         @OA\Schema(type="integer", example=3)
     *     ),
     *     @OA\Parameter(
     *         name="is_role",
     *         in="query",
     *         description="اگر مقدار 1 ارسال شود فقط کاربرانی که حداقل یک نقش دارند بازگردانده می‌شوند",
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="remaining_days_from",
     *         in="query",
     *         description="تعداد روزهای باقی‌مانده حداقل تا پایان برنامه هفتگی",
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Parameter(
     *         name="remaining_days_to",
     *         in="query",
     *         description="تعداد روزهای باقی‌مانده حداکثر تا پایان برنامه هفتگی",
     *         @OA\Schema(type="integer", example=7)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="لیست کاربران با موفقیت بازگردانده شد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="result", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="first_name", type="string", example="علی"),
     *                     @OA\Property(property="last_name", type="string", example="رضایی"),
     *                     @OA\Property(property="phone", type="string", example="09121234567"),
     *                     @OA\Property(property="gender", type="string", example="male"),
     *                     @OA\Property(property="age", type="integer", nullable=true, example=28),
     *                     @OA\Property(property="expire_at", type="string", format="date-time", nullable=true, example="2025-12-31"),
     *                     @OA\Property(property="subscription_day", type="integer", nullable=true, example=12, description="تعداد روزهای باقی‌مانده تا انقضای اشتراک"),
     *                     @OA\Property(property="remaining_days", type="integer", nullable=true, example=3, description="تعداد روزهای باقی‌مانده تا پایان برنامه هفتگی (فقط اگر در آینده باشد)"),
     *                     @OA\Property(property="has_exercise_program", type="integer", nullable=true, example=12, description="داشتن برنامه ورزشی"),
     *                     @OA\Property(property="roles", type="array", @OA\Items(type="string", example="super_admin"))
     *                 )
     *             ),
     *             @OA\Property(property="totalCount", type="integer", example=150)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="عدم دسترسی (توکن نامعتبر یا سطح دسترسی کافی نیست)"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="شما مجوز مشاهده این اطلاعات را ندارید"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pageSize = (int)($request->pagesize ?? 20);
        $query = User::query();

        if ($request->filled('name')) {
            $query->where(function($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->name}%")
                ->orWhere('last_name', 'like', "%{$request->name}%");
            });
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }

        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // ✅ فیلتر براساس role_id مشخص
        if ($request->filled('role')) {
            $roleId = (int) $request->role;
            $query->join('model_has_roles as mhr', function ($join) {
                $join->on('mhr.model_id', '=', 'diet_users.id')
                    ->where('mhr.model_type', '=', User::class);
            })
            ->where('mhr.role_id', $roleId)
            ->select('diet_users.*')
            ->distinct();
        }

        // ✅ فیلتر برای کاربرانی که حداقل یک رول دارند
        if ($request->boolean('is_role')) {
            $query->whereIn('diet_users.id', function ($subquery) {
                $subquery->select('model_id')
                    ->from('model_has_roles')
                    ->where('model_type', User::class);
            });
        }

        // فیلتر بر اساس expire_days
        if ($request->filled('expire_days_from') && $request->filled('expire_days_to')) {
            $expireDaysFrom = (int)$request->expire_days_from;
            $expireDaysTo = (int)$request->expire_days_to;
            if($expireDaysTo == 0 && $expireDaysFrom == 0) {
                $query->where(function ($q) {
                    $q->whereNull('expire_at')
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('expire_at')
                            ->whereRaw('DATEDIFF(expire_at, CURDATE()) <= 0');
                    });
                });

            }
            else
            {
                $query->where(function ($q) use ($expireDaysFrom , $expireDaysTo) {
                    $q->/*whereNull('expire_at')
                    ->or*/Where(function ($q2) use ($expireDaysFrom , $expireDaysTo) {
                        $q2->whereNotNull('expire_at')
                            ->whereRaw('DATEDIFF(expire_at, CURDATE()) <= ?', [$expireDaysTo])
                            ->whereRaw('DATEDIFF(expire_at, CURDATE()) >= ?', [$expireDaysFrom]);
                    });
                });

            }
        }
        elseif($request->filled('expire_days_from'))
        {
            $expireDaysFrom = (int)$request->expire_days_from;
            $query->where(function ($q) use ($expireDaysFrom) {
                $q->Where(function ($q2) use ($expireDaysFrom) {
                    $q2->whereNotNull('expire_at')
                        ->whereRaw('DATEDIFF(expire_at, CURDATE()) >= ?', [$expireDaysFrom]);
                });
            });
        }
        elseif($request->filled('expire_days_to'))
        {
            $expireDaysTo = (int)$request->expire_days_to;
            $query->where(function ($q) use ($expireDaysTo) {
                $q->Where(function ($q2) use ($expireDaysTo) {
                    $q2->whereNotNull('expire_at')
                        ->whereRaw('DATEDIFF(expire_at, CURDATE()) <= ?', [$expireDaysTo]);
                });
            });
        }


        if ($request->filled('age')) {
            $query->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) = ?', [$request->age]);
        }

        // ✅ فیلتر بر اساس تعداد روزهای باقی‌مانده تا پایان رژیم هفتگی
        if ($request->filled('remaining_days_from') && $request->filled('remaining_days_to')) {
            $remainingDaysFrom = (int)$request->remaining_days_from;
            $remainingDaysTo = (int)$request->remaining_days_to;

            $query->whereIn('diet_users.id', function ($subquery) use ($remainingDaysFrom , $remainingDaysTo) {
                $subquery->select('userId')
                    ->from('diet_user_weekly')
                    ->whereRaw('DATEDIFF(todate, CURDATE()) <= ?', [$remainingDaysTo])
                    ->whereRaw('DATEDIFF(todate, CURDATE()) >= ?', [$remainingDaysFrom]);
            });
        }
        elseif ($request->filled('remaining_days_from')) {
            $remainingDaysFrom = (int)$request->remaining_days_from;
            $query->whereIn('diet_users.id', function ($subquery) use ($remainingDaysFrom) {
                $subquery->select('userId')
                    ->from('diet_user_weekly')
                     ->whereRaw('DATEDIFF(todate, CURDATE()) >= ?', [$remainingDaysFrom]);
            });
        }
        elseif ($request->filled('remaining_days_to')) {
            $remainingDaysTo = (int)$request->remaining_days_to;

            $query->whereIn('diet_users.id', function ($subquery) use ($remainingDaysTo) {
                $subquery->select('userId')
                    ->from('diet_user_weekly')
                    ->whereRaw('DATEDIFF(todate, CURDATE()) <= ?', [$remainingDaysTo]);
            });
        }



        $totalCount = $query->count();
        $users = $query->orderBy('id', 'desc')->paginate($pageSize);
        $users = array_map(function ($user) {
            if (empty($user->login_token)) {
                $user->login_token = Str::random(60);
                $user->save();
            }

            $age = $user->birth_date ? Carbon::parse($user->birth_date)->age : null;
            $roles = $user->getRoleNames(); // خروجی: ["admin", "editor", ...]
            $subscriptionDay = $user->expire_at ? Carbon::parse($user->expire_at)->diffInDays(Carbon::today(), false) : null;

            // 🟡 آخرین رکورد رژیم هفتگی کاربر
            $latestWeekly = \App\Models\DietUserWeekly::where('userId', $user->id)
                ->orderByDesc('todate')
                ->first();

            // 🟢 محاسبه روزهای باقی‌مانده فقط اگر todate در آینده باشد
            $remainingDays = null;
            if ($latestWeekly && $latestWeekly->todate) {
                $diff = Carbon::today()->diffInDays(Carbon::parse($latestWeekly->todate), false);
                $remainingDays = $diff > 0 ? $diff : null;
            }
            $exerciseProgram = ExerciseUsersProgram::where('user_id', $user->id)
            ->where('expire_at', '>=', date('Y-m-d'))
            ->orderByDesc('id')
            ->first();

            $programs = null;
            if ($exerciseProgram) {
                $items = $exerciseProgram->items->map(function ($item) {
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

                $programs = [
                    'id' => $exerciseProgram->id,
                    'user_id' => $exerciseProgram->user_id,
                    'name' => $exerciseProgram->program?->name,
                    'description' => $exerciseProgram->program?->description,
                    'items' => $items,
                ];
            }
            return [
                'id' => $user->id,
                'gender' => $user->gender,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'food_culture' => $user->food_culture,
                'phone' => $user->phone,
                'email' => $user->email,
                'birth_date' => $user->birth_date,
                'age' => $age,
                'height' => $user->height,
                'weight' => $user->weight,
                'target_weight' => $user->target_weight,
                'wrist_size' => $user->wrist_size,
                'pregnancy_week' => $user->pregnancy_week,
                'country_id' => $user->country_id,
                'state_id' => $user->state_id,
                'city_id' => $user->city_id,
                'postal_code' => $user->postal_code,
                'address' => $user->address,
                'diet_type_id' => $user->diet_type_id,
                'diet_type' => $user->diet_type_id ? DietType::from($user->diet_type_id)->label() : null,
                'food_type_id' => $user->food_type_id,
                'food_type' => $user->food_type_id ? FoodType::from($user->food_type_id)->label() : null,
                'daily_activity_level' => $user->daily_activity_level,
                'diet_goal' => $user->diet_goal,
                'has_diet_history' => $user->has_diet_history,
                'diet_history' => $user->diet_history,
                'package' => $user->package,
                'inactive' => $user->inactive,
                'created_at' => $user->created_at,
                'expire_at' => $user->expire_at,
                'subscription_day' => $subscriptionDay,
                'roles' => $roles,
                'remaining_days' => $remainingDays,
                'has_exercise_program' => !is_null($programs),
                'programs' => $programs,
                'loginLink' => 'https://di3t-club.com/login/callback-email?token=' . $user->login_token
            ];
        }, $users->items());

        return response()->json([
            'result' => $users,
            'totalCount' => $totalCount,
        ]);
    }


    /**
     * @OA\Get(
     *     path="/api/diet-users/{id}",
     *     summary="نمایش اطلاعات کامل کاربر رژیمی همراه با آخرین برنامه غذایی و تغییرات وزن",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه کاربر رژیمی",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="age", type="integer", nullable=true),
     *             @OA\Property(property="diet_type", type="string", nullable=true),

    *             @OA\Property(
    *                 property="latest_diet_plan",
    *                 type="object",
    *                 nullable=true,
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="fromdate", type="string", format="date"),
    *                 @OA\Property(property="todate", type="string", format="date"),
    *                 @OA\Property(property="weekly", type="string"),
    *                 @OA\Property(property="calories", type="number"),
    *                 @OA\Property(property="weight", type="number"),
    *                 @OA\Property(property="items", type="object", example={
    *                     "شنبه": {
    *                         "صبحانه": {
    *                             {"mealItemId": 1, "itemTitle": "تخم مرغ", "unit": "عدد", "unitCount": 2}
    *                         }
    *                     }
    *                 })
    *             ),

    *             @OA\Property(
    *                 property="weights",
    *                 type="array",
    *                 description="لیست تغییرات وزن کاربر",
    *                 @OA\Items(
    *                     type="object",
    *                     @OA\Property(property="weight", type="number", example=89.5),
    *                     @OA\Property(property="date", type="string", format="date", example="2025-01-17"),
    *                     @OA\Property(property="type", type="string", enum={"initial", "weekly"}, example="weekly")
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
    *     @OA\Response(response=404, description="یافت نشد")
    * )
    */

    public function show($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item = User::findOrFail($id);

        $age = $item->birth_date ? Carbon::parse($item->birth_date)->age : null;
        $dietTypeLabel = $item->diet_type_id ? DietType::from($item->diet_type_id)->label() : null;
        $foodTypeLabel = $item->food_type_id ? FoodType::from($item->food_type_id)->label() : null;

        $latestPlan = $item->dietUserWeeklies()->with(['weekly', 'items.mealItem', 'items.dietWeeklyMeal'])->orderByDesc('id')->first();

        $latestPlanOutput = null;
        $mealOrder = [
            MealType::Breakfast->label(),
            MealType::MorningSnack->label(),
            MealType::PreLunch->label(),
            MealType::Lunch->label(),
            MealType::AfternoonSnack2->label(),
            MealType::Dinner->label(),
            MealType::SugarPortion->label(),
            MealType::DairyPortion->label(),
            MealType::FatPortion->label(),
        ];
        if ($latestPlan) {
            $latestPlanOutput = [
                'id' => $latestPlan->id,
                'userId' => $latestPlan->userId,
                'userName' => $latestPlan->user?->first_name . ' ' . $latestPlan->user?->last_name,
                'fromdate' => $latestPlan->fromdate,
                'todate' => $latestPlan->todate,
                'weeklyId' => $latestPlan->weeklyId,
                'weekly' => $latestPlan->weekly?->name,
                'calories' => $latestPlan->calories,
                'weight' => $latestPlan->weight,
                'created_at' => $latestPlan->created_at,
                'updated_at' => $latestPlan->updated_at,
                'items' => [],
            ];

            foreach ($latestPlan->items as $planItem) {
                $day = $planItem->dietWeeklyMeal?->day;
                $mealTypeLabel = $planItem->dietWeeklyMeal?->mealTypeId
                    ? MealType::from($planItem->dietWeeklyMeal->mealTypeId)->label()
                    : null;

                $latestPlanOutput['items'][$day][$mealTypeLabel][] = [
                    'mealItemId' => $planItem->mealItemId,
                    'itemTitle' => $planItem->mealItem?->name,
                    'unit' => $planItem->mealItem?->unit,
                    'unitCount' => $planItem->unitCount,
                ];
            }

            foreach ($latestPlanOutput['items'] as $day => $meals) {
                // مرتب‌سازی meal ها با توجه به ترتیب mealOrder
                uksort($meals, function ($a, $b) use ($mealOrder) {
                    $posA = array_search($a, $mealOrder);
                    $posB = array_search($b, $mealOrder);
                    return ($posA === false ? PHP_INT_MAX : $posA) <=> ($posB === false ? PHP_INT_MAX : $posB);
                });

                // ذخیره‌ی مجدد meals مرتب‌شده در خروجی نهایی
                $latestPlanOutput['items'][$day] = $meals;
            }

        }

        // پیش از return
        $weights = [];

        if ($item->weight) {
            $weights[] = [
                'weight' => $item->weight,
                'date' => $item->created_at->toDateString(),
                'type' => 'initial',
            ];
        }

        foreach ($item->dietUserWeeklies()->orderByDesc('id')->get() as $weekly) {
            if ($weekly->weight) {
                $weights[] = [
                    'weight' => $weekly->weight,
                    'date' => $weekly->fromdate ? Carbon::parse($weekly->fromdate)->toDateString() : $weekly->created_at->toDateString(),
                    'type' => 'weekly',
                ];
            }
        }

        $roles = $item->getRoleNames();
        $subscriptionDay = $item->expire_at ? Carbon::parse($item->expire_at)->diffInDays(Carbon::today(), false) : null;

        // 🟡 آخرین رکورد رژیم هفتگی کاربر
        $latestWeekly = \App\Models\DietUserWeekly::where('userId', $item->id)
            ->orderByDesc('todate')
            ->first();

        // 🟢 محاسبه روزهای باقی‌مانده فقط اگر todate در آینده باشد
        $remainingDays = null;
        if ($latestWeekly && $latestWeekly->todate) {
            $diff = Carbon::today()->diffInDays(Carbon::parse($latestWeekly->todate), false);
            $remainingDays = $diff > 0 ? $diff : null;
        }

        $exerciseProgram = ExerciseUsersProgram::where('user_id', $id)
        ->where('expire_at', '>=', date('Y-m-d'))
        ->orderByDesc('id')
        ->first();

        $programs = null;
        if ($exerciseProgram) {
            $items = $exerciseProgram->items->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'frequency' => $item->frequency,
                    'set' => $item->set,
                    'name_en' => $item->exercise?->name_en,
                    'name_ar' => $item->exercise?->name_ar,
                    'day' => $item->item?->day,
                ];
            });

            $programs = [
                'id' => $exerciseProgram->id,
                'user_id' => $exerciseProgram->user_id,
                'expire_at' => $exerciseProgram->expire_at,
                'name' => $exerciseProgram->program?->name,
                'description' => $exerciseProgram->program?->description,
                'location_id' => $exerciseProgram->program?->location_id,
                'location' => $exerciseProgram->program?->location_id ? ExerciseLocations::from($exerciseProgram->program?->location_id)->label() : null,
                'items' => $items,
            ];
        }
        /*if (empty($item->ai_description)) {
            $aiProfileData = [
                'gender' => $item->gender,
                'age' => $age,
                'height' => $item->height,
                'current_weight' => $item->weight,
                'target_weight' => $item->target_weight,
                'diet_type' => $dietTypeLabel,
                'food_type' => $foodTypeLabel,
                'daily_activity_level' => $item->daily_activity_level,
                'diet_goal' => $item->diet_goal,
                'has_diet_history' => $item->has_diet_history,
                'diet_history' => $item->diet_history,
                'pregnancy_week' => $item->pregnancy_week,
                'latest_calories' => $latestPlan?->calories,
                'exercise_program' => $programs ? true : false,
            ];
            $openAI = app(OpenAIService::class);

            $aiDescription = $openAI->chat([
            [
                'role' => 'system',
                'content' => 'You are a professional Persian dietitian and health analyst.'
            ],
            [
                'role' => 'user',
                'content' => "
        بر اساس اطلاعات زیر، یک توضیح مفصل، علمی و کاربردی درباره وضعیت این فرد بنویس.
        لحن: رسمی و قابل ارائه به کارشناس تغذیه.

        اطلاعات فرد:
        " . json_encode($aiProfileData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            ]
            ]);
            $item->ai_description = $aiDescription;
            $item->save();
        }*/

        return response()->json([
            'id' => $item->id,
            'gender' => $item->gender,
            'first_name' => $item->first_name,
            'last_name' => $item->last_name,
            'food_culture' => $item->food_culture,
            'phone' => $item->phone,
            'email' => $item->email,
            'birth_date' => $item->birth_date,
            'age' => $age,
            'height' => $item->height,
            'weight' => $item->weight,
            'weight' => $item->target_weight,
            'wrist_size' => $item->wrist_size,
            'pregnancy_week' => $item->pregnancy_week,
            'country_id' => $item->country_id,
            'state_id' => $item->state_id,
            'city_id' => $item->city_id,
            'postal_code' => $item->postal_code,
            'address' => $item->address,
            'diet_type_id' => $item->diet_type_id,
            'diet_type' => $dietTypeLabel,
            'food_type_id' => $item->food_type_id,
            'food_type' => $foodTypeLabel,
            'daily_activity_level' => $item->daily_activity_level,
            'diet_goal' => $item->diet_goal,
            'has_diet_history' => $item->has_diet_history,
            'diet_history' => $item->diet_history,
            'package' => $item->package,
            'created_at' => $item->created_at,
            'latest_diet_plan' => $latestPlanOutput,
            'weights' => $weights,
            'roles' => $roles,
            'subscription_day' => $subscriptionDay,
            'remaining_days' => $remainingDays,
            'has_exercise_program' => !is_null($programs),
            'programs' => $programs,
            //'ai_description' => $item->ai_description,
            'loginLink' => 'https://di3t-club.com/login/callback-email?token=' . $item->login_token
        ]);
    }


    /**
     * @OA\Delete(
     *     path="/api/diet-users/{id}",
     *     summary="حذف یک کاربر رژیم غذایی",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه کاربر",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="کاربر حذف شد"),
     *     @OA\Response(response=404, description="کاربر یافت نشد")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'کاربر یافت نشد'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'کاربر حذف شد']);
    }

    /**
     * @OA\Get(
     *     path="/api/food-cultures",
     *     summary="لیست فرهنگ‌های غذایی",
     *     tags={"Enums"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="label", type="string")
     *         ))
     *     )
     * )
     */
    public function foodCultureList()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert', 'nutrition_expert', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $list = collect(FoodCulture::cases())->map(function ($item) {
            return [
                'id' => $item->value,
                'label' => $item->label(),
            ];
        });

        return response()->json($list);
    }

    /**
     * @OA\Get(
     *     path="/api/diet/types",
     *     summary="لیست انواع رژیم غذایی",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="label", type="string")
     *         ))
     *     )
     * )
     */
    public function dietTypes()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $types = collect(DietType::cases())->map(fn($type) => [
            'id' => $type->value,
            'label' => $type->label()
        ]);

        return response()->json($types);
    }

    /**
     * @OA\Get(
     *     path="/api/diet/activity-levels",
     *     summary="لیست فعالیت‌های روزانه",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="label", type="string")
     *         ))
     *     )
     * )
     */
    public function activityLevels()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json(
            collect(DailyActivityLevel::cases())->map(fn($a) => ['id' => $a->value, 'label' => $a->label()])
        );
    }

    /**
     * @OA\Get(
     *     path="/api/diet/goals",
     *     summary="لیست اهداف رژیم غذایی",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="label", type="string")
     *         ))
     *     )
     * )
     */
    public function dietGoals()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json(
            collect(DietGoal::cases())->map(fn($a) => ['id' => $a->value, 'label' => $a->label()])
        );
    }

    /**
     * @OA\Get(
     *     path="/api/diet/history-options",
     *     summary="لیست گزینه‌های سابقه رژیم",
     *     tags={"DietUser"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="label", type="string")
     *         ))
     *     )
     * )
     */
    public function dietHistories()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json(
            collect(HasDietHistory::cases())->map(fn($a) => ['id' => $a->value, 'label' => $a->label()])
        );
    }

    /**
     * @OA\Post(
     *     path="/api/diet-users/{id}/status",
     *     summary="فعال یا غیرفعال کردن کاربر رژیم",
     *     description="تغییر وضعیت فعال/غیرفعال کاربر از طریق فیلد inactive",
     *     operationId="toggleDietUserStatus",
     *     tags={"Diet Users"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه کاربر رژیم",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"inactive"},
     *             @OA\Property(property="inactive", type="boolean", example=false)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="وضعیت کاربر تغییر کرد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="کاربر فعال شد."),
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="inactive", type="boolean")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="درخواست نامعتبر"
     *     )
     * )
     */

    public function toggleStatus($id, Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'inactive' => 'required|boolean',
        ]);

        $dietUser = User::findOrFail($id);
        $dietUser->inactive = $request->inactive;
        $dietUser->save();

        return response()->json([
            'message' => $request->inactive ? 'کاربر غیرفعال شد.' : 'کاربر فعال شد.',
            'id' => $dietUser->id,
            'inactive' => $dietUser->inactive,
        ]);
    }

}
