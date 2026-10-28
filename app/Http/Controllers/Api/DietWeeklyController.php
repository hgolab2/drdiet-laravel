<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DietWeekly;
use App\Models\DietWeeklyMeal;
use App\Models\DietWeeklyCulture;
use App\Models\DietWeeklyType;
use App\Enums\FoodCulture;
use App\Enums\DietType;
use App\Enums\FoodType;
use App\Enums\WeightGoal;

class DietWeeklyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Post(
     *     path="/api/diet-weekly",
     *     summary="ایجاد برنامه هفتگی جدید",
     *     tags={"DietWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "foodCultureIds", "typeIds", "meals"},
     *             @OA\Property(property="name", type="string", example="برنامه هفته اول"),
     *             @OA\Property(property="days_count", type="integer", example=7, description="تعداد روزهای برنامه (۱ تا ۳۱، پیش‌فرض ۷)"),
     *             @OA\Property(property="food_type_id", type="integer", example=1),
     *             @OA\Property(
     *                 property="foodCultureIds",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             ),
     *             @OA\Property(
     *                 property="typeIds",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             ),
     *             @OA\Property(
     *                 property="meals",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="mealId", type="integer", example=4),
     *                     @OA\Property(property="mealTypeId", type="integer", example=2),
     *                     @OA\Property(property="day", type="integer", example=3)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="برنامه با موفقیت ایجاد شد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'food_type_id' => 'integer',
            'foodCultureIds' => 'required|array',
            'foodCultureIds.*' => 'required|integer',
            'typeIds' => 'required|array',
            'typeIds.*' => 'required|integer',
            'meals' => 'required|array',
            'meals.*.mealId' => 'required|integer',
            'meals.*.mealTypeId' => 'required|integer',
            'days_count' => 'nullable|integer|min:1|max:' . DietWeekly::MAX_DAYS,
            'meals.*.day' => 'required|integer|min:1|max:' . max(1, (int) ($request->days_count ?? DietWeekly::DEFAULT_DAYS)),
        ]);

        $weekly = DietWeekly::create([
            'name' => $request->name,
            'food_type_id' => $request->food_type_id,
            'days_count' => $request->days_count ?? DietWeekly::DEFAULT_DAYS,
        ]);

        foreach ($request->foodCultureIds as $cultureId) {
            DietWeeklyCulture::create([
                'diet_weekly_id' => $weekly->id,
                'food_culture_id' => $cultureId
            ]);
        }

        foreach ($request->typeIds as $typeId) {
            DietWeeklyType::create([
                'diet_weekly_id' => $weekly->id,
                'type_id' => $typeId
            ]);
        }

        foreach ($request->meals as $meal) {
            DietWeeklyMeal::create([
                'diet_weekly_id' => $weekly->id,
                'mealId' => $meal['mealId'],
                'mealTypeId' => $meal['mealTypeId'],
                'day' => $meal['day'],
            ]);
        }

        return response()->json(['message' => 'برنامه با موفقیت ایجاد شد.'], 201);
    }

        /**
     * @OA\Get(
     *     path="/api/diet-weekly/{id}",
     *     summary="نمایش جزئیات یک برنامه هفتگی",
     *     tags={"DietWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه برنامه هفتگی",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="اطلاعات برنامه هفتگی همراه با وعده‌ها",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="foodCultureId", type="integer"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time"),
     *             @OA\Property(property="meals", type="array", @OA\Items(
     *                 @OA\Property(property="mealId", type="integer"),
     *                 @OA\Property(property="mealTypeId", type="integer"),
     *                 @OA\Property(property="day", type="integer")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="برنامه یافت نشد")
     * )
     */

    public function show($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert' , 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $weakly = DietWeekly::with('meals')->find($id);

        if (!$weakly) {
            return response()->json(['message' => 'برنامه یافت نشد.'], 404);
        }

        return response()->json([
            'id' => $weakly->id,
            'name' => $weakly->name,
            'days_count' => $weakly->cycleDays(),
            'food_type_id' => $weakly->food_type_id,
            'food_type' => $weakly->food_type_id ? FoodType::from($weakly->food_type_id)->label() : null,

            'foodCultures' => $weakly->foodCultures->map(function ($f) {
                return [
                    'id' => $f->food_culture_id,
                    'label' => $f->food_culture_id ? FoodCulture::from($f->food_culture_id)->label() : null,
                ];
            }),

            'types' => $weakly->types->map(function ($f) {
                return [
                    'id' => $f->type_id,
                    'label' => $f->type_id ? DietType::from($f->type_id)->label() : null,
                ];
            }),

            'meals' => $weakly->meals->map(fn($i) => [
                'mealId' => $i->mealId,
                'mealName' => $i->mealId > 0 ? $i->meal?->name : null,
                'mealTypeId' => $i->mealTypeId,
                'day' => $i->day
            ])
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/diet-weekly/{id}",
     *     summary="ویرایش برنامه هفتگی",
     *     tags={"DietWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه برنامه هفتگی",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "foodCultureIds", "typeIds", "meals"},
     *             @OA\Property(property="name", type="string", example="برنامه هفته دوم"),
     *             @OA\Property(property="days_count", type="integer", example=13, description="تعداد روزهای برنامه (۱ تا ۳۱)"),
     *             @OA\Property(property="food_type_id", type="integer", example=2),
     *             @OA\Property(
     *                 property="foodCultureIds",
     *                 type="array",
     *                 @OA\Items(type="integer", example=2)
     *             ),
     *             @OA\Property(
     *                 property="typeIds",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             ),
     *             @OA\Property(
     *                 property="meals",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="mealId", type="integer", example=5),
     *                     @OA\Property(property="mealTypeId", type="integer", example=1),
     *                     @OA\Property(property="day", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="برنامه با موفقیت ویرایش شد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="برنامه یافت نشد")
     * )
     */


    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $weekly = DietWeekly::find($id);
        if (!$weekly) {
            return response()->json(['message' => 'برنامه یافت نشد.'], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'food_type_id' => 'integer',
            'food_culture_id' => 'integer',
            'foodCultureIds' => 'required|array',
            'foodCultureIds.*' => 'required|integer',
            'typeIds' => 'required|array',
            'typeIds.*' => 'required|integer',
            'meals' => 'array',
            'meals.*.mealId' => 'required|integer',
            'meals.*.mealTypeId' => 'required|integer',
            'days_count' => 'nullable|integer|min:1|max:' . DietWeekly::MAX_DAYS,
            'meals.*.day' => 'required|integer|min:1|max:' . max(1, (int) ($request->days_count ?? $weekly->cycleDays())),
        ]);

        $weekly->update([
            'name' => $request->name,
            'food_type_id' => $request->food_type_id,
            'days_count' => $request->days_count ?? $weekly->cycleDays(),
        ]);

        DietWeeklyCulture::where('diet_weekly_id', $weekly->id)->delete();
        foreach ($request->foodCultureIds as $cultureId) {
            DietWeeklyCulture::create([
                'diet_weekly_id' => $weekly->id,
                'food_culture_id' => $cultureId
            ]);
        }

        DietWeeklyType::where('diet_weekly_id', $weekly->id)->delete();
        foreach ($request->typeIds as $typeId) {
            DietWeeklyType::create([
                'diet_weekly_id' => $weekly->id,
                'type_id' => $typeId
            ]);
        }

        DietWeeklyMeal::where('diet_weekly_id', $weekly->id)->delete();

        foreach ($request->meals as $meal) {
            DietWeeklyMeal::create([
                'diet_weekly_id' => $weekly->id,
                'mealId' => $meal['mealId'],
                'mealTypeId' => $meal['mealTypeId'],
                'day' => $meal['day'],
            ]);
        }

        return response()->json(['message' => 'برنامه با موفقیت ویرایش شد.']);
    }


    /**
     * @OA\Get(
     *     path="/api/diet-weekly",
     *     summary="دریافت لیست برنامه‌های هفتگی با فیلتر و صفحه‌بندی",
     *     tags={"DietWeekly"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="جستجو بر اساس نام برنامه هفتگی",
     *         required=false,
     *         @OA\Schema(type="string", example="برنامه کتو")
     *     ),
     *     @OA\Parameter(
     *         name="food_type_id",
     *         in="query",
     *         description="فیلتر بر اساس نوع غذا (شناسه)",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="foodCultures",
     *         in="query",
     *         description="فیلتر بر اساس فرهنگ غذایی (لیست ID جداشده با کاما یا آرایه)",
     *         required=false,
     *         @OA\Schema(type="string", example="1,3")
     *     ),
     *     @OA\Parameter(
     *         name="types",
     *         in="query",
     *         description="فیلتر بر اساس نوع رژیم (لیست ID جداشده با کاما یا آرایه)",
     *         required=false,
     *         @OA\Schema(type="string", example="4,6")
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم در هر صفحه (پیش‌فرض 20)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="لیست برنامه‌های هفتگی با تعداد کل",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="result",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="برنامه رژیم مدیترانه‌ای"),
     *                     @OA\Property(property="food_type_id", type="integer", example=2),
     *                     @OA\Property(property="food_type", type="string", example="ناهار"),
     *                     @OA\Property(
     *                         property="foodCultures",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="label", type="string", example="ایرانی")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="types",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=4),
     *                             @OA\Property(property="label", type="string", example="کیتو")
     *                         )
     *                     ),
     *                     @OA\Property(
     *                         property="meals",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="mealId", type="integer", example=12),
     *                             @OA\Property(property="mealName", type="string", example="سوپ جو"),
     *                             @OA\Property(property="mealTypeId", type="integer", example=3),
     *                             @OA\Property(property="day", type="string", example="دوشنبه")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="totalCount", type="integer", example=50)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز (توکن معتبر نیست)"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="کاربر مجاز به مشاهده این بخش نیست"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert' , 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $pageSize = $request->pagesize ?? 20;
        $query = DietWeekly::with(['cultures', 'types'])->where('type' , 'admin');

        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        // 🔹 فیلتر بر اساس نوع غذا
        if ($request->filled('food_type_id')) {
            $query->where('food_type_id', $request->food_type_id);
        }

        // 🔹 فیلتر بر اساس فرهنگ غذایی (foodCultures)
        if ($request->filled('foodCultures')) {
            $cultureIds = is_array($request->foodCultures)
                ? $request->foodCultures
                : explode(',', $request->foodCultures);

            $query->whereHas('cultures', function ($q) use ($cultureIds) {
                $q->whereIn('food_culture_id', $cultureIds);
            });
        }

        // 🔹 فیلتر بر اساس نوع رژیم (types)
        if ($request->filled('types')) {
            $typeIds = is_array($request->types)
                ? $request->types
                : explode(',', $request->types);

            $query->whereHas('types', function ($q) use ($typeIds) {
                $q->whereIn('type_id', $typeIds);
            });
        }

        $total = $query->count();
        $data = $query->orderBy('id', 'desc')->paginate($pageSize);
        $data = collect($data->items())->map(function ($weakly) {
            return [
                'id' => $weakly->id,
                'name' => $weakly->name,
                'days_count' => $weakly->cycleDays(),
                'food_type_id' => $weakly->food_type_id,
                'food_type' => $weakly->food_type_id ? FoodType::from($weakly->food_type_id)->label() : null,

                'foodCultures' => $weakly->foodCultures->map(function ($f) {
                    return [
                        'id' => $f->food_culture_id,
                        'label' => $f->food_culture_id ? FoodCulture::from($f->food_culture_id)->label() : null,
                    ];
                }),

                'types' => $weakly->types->map(function ($f) {
                    return [
                        'id' => $f->type_id,
                        'label' => $f->type_id ? DietType::from($f->type_id)->label() : null,
                    ];
                }),

                /*'meals' => $weakly->meals->map(fn($i) => [
                    'mealId' => $i->mealId,
                    'mealName' => $i->mealId > 0 ? $i->meal?->name : null,
                    'mealTypeId' => $i->mealTypeId,
                    'day' => $i->day
                ])*/
            ];
        });
        return response()->json([
            'result' => $data,
            'totalCount' => $total
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/diet-weekly/{id}",
     *     summary="حذف یک برنامه هفتگی",
     *     tags={"DietWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه برنامه هفتگی",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="برنامه حذف شد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="برنامه یافت نشد")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $weekly = DietWeekly::find($id);
        if (!$weekly) {
            return response()->json(['message' => 'برنامه یافت نشد.'], 404);
        }
        $weekly->delete();
        DietWeeklyMeal::where('diet_weekly_id', $id)->delete();
        DietWeeklyType::where('diet_weekly_id', $id)->delete();
        return response()->json(['message' => 'برنامه حذف شد.']);
    }

    /**
     * @OA\Post(
     *     path="/api/diet-weekly/day-meals",
     *     summary="دریافت وعده‌های غذایی یک روز مشخص از برنامه هفتگی",
     *     tags={"DietWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"weeklyId", "day"},
     *             @OA\Property(property="weeklyId", type="integer", example=1),
     *             @OA\Property(property="day", type="integer", example=1, description="روز برنامه (1 تا تعداد روزهای برنامه)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="لیست وعده‌های غذایی در روز مشخص",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="mealId", type="integer"),
     *             @OA\Property(property="mealTypeId", type="integer"),
     *             @OA\Property(property="day", type="integer")
     *         ))
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function mealsByDay(Request $request)
    {
        $request->validate([
            'weeklyId' => 'required|integer',
            'day' => 'required|integer|min:1|max:' . DietWeekly::MAX_DAYS,
        ]);

        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meals = DietWeeklyMeal::where('diet_weekly_id', $request->weeklyId)
            ->where('day', $request->day)
            ->get();

        return response()->json($meals);
    }

    /**
     * @OA\Post(
     *     path="/api/diet-weekly/type-meals",
     *     summary="دریافت وعده‌های غذایی با نوع مشخص در برنامه هفتگی",
     *     tags={"DietWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"weeklyId", "mealTypeId"},
     *             @OA\Property(property="weeklyId", type="integer", example=1),
     *             @OA\Property(property="mealTypeId", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="لیست وعده‌های غذایی بر اساس نوع مشخص",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="mealId", type="integer"),
     *             @OA\Property(property="mealTypeId", type="integer"),
     *             @OA\Property(property="day", type="integer")
     *         ))
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function mealsByType(Request $request)
    {
        $request->validate([
            'weeklyId' => 'required|integer',
            'mealTypeId' => 'required|integer',
        ]);

        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meals = DietWeeklyMeal::where('diet_weekly_id', $request->weeklyId)
            ->where('mealTypeId', $request->mealTypeId)
            ->get();

        return response()->json($meals);
    }
}
