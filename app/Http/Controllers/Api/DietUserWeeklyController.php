<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DietUserWeekly;
use App\Models\DietUserWeeklyItem;
use App\Models\User;
use App\Models\DietWeeklyMeal;
use App\Models\DietItem;
use App\Models\Calorie;
use App\Models\DietMealItem;
use App\Models\Subscription;
use App\Models\DietWeekly;
use App\Enums\DailyActivityLevel;
use App\Enums\MealType;
use App\Enums\FoodType;
use App\Enums\DietType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class DietUserWeeklyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function getCalorie($calorieData, $fieldName, $targetCalories)
    {
        if ($calorieData->{$fieldName . 'Type'} == 'amount') {
            return $calorieData->{$fieldName} ?? null;
        } else {
            $fields = [
                'breakfast', 'morningSnack', 'preLunch', 'lunch',
                'afternoonSnack2', 'dinner', 'sugarPortion',
                'fatPortion', 'dairyPortion'
            ];

            $sum = 0;
            foreach ($fields as $f) {
                if ($calorieData->{$f . 'Type'} == 'amount') {
                    $sum += $calorieData->{$f} ?? 0;
                }
            }

            return (($targetCalories - $sum) * ($calorieData->{$fieldName} ?? 0)) / 100;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/user-weekly",
     *     summary="ثبت برنامه رژیم هفتگی برای کاربر",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"userId", "fromdate", "todate", "weeklyId"},
     *             @OA\Property(property="userId", type="integer", example=5),
     *             @OA\Property(property="fromdate", type="string", format="date", example="2025-07-01"),
     *             @OA\Property(property="weeklyId", type="integer", example=2),
     *             @OA\Property(property="weight", type="integer", example=85),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="برنامه رژیم با موفقیت ثبت شد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="ثبت شد."))
     *     ),
     *     @OA\Response(response=401, description="عدم دسترسی"),
     *     @OA\Response(response=404, description="کاربر یافت نشد"),
     *     @OA\Response(response=422, description="ورودی نامعتبر یا ناقص")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert' , 'support' , 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'userId' => 'required|integer',
            'fromdate' => 'required|date',
            'weeklyId' => 'required|integer',
            'weight' => 'required|numeric',
        ]);
        $fromDate = Carbon::parse($request->fromdate);
        $todate = $fromDate->copy()->addDays(7)->format('Y-m-d');

        $dietUser = User::find($request->userId);
        if (!$dietUser) {
            return response()->json(['message' => 'کاربر یافت نشد.'], 404);
        }

        $birthDate = $dietUser->birth_date;
        if (!$birthDate) {
            return response()->json(['message' => 'تاریخ تولد موجود نیست.'], 422);
        }

        $age = Carbon::parse($birthDate)->age;
        $weight = $request->weight;
        $height = $dietUser->height;
        $gender = $dietUser->gender;

        if ($gender === 'male') {
            $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
        } elseif ($gender === 'female') {
            $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
        } else {
            return response()->json(['message' => 'جنسیت نامعتبر است.'], 422);
        }


        $activityLevel = DailyActivityLevel::tryFrom($dietUser->daily_activity_level);
        if (!$activityLevel) {
            return response()->json(['message' => 'سطح فعالیت نامعتبر است.'], 422);
        }
        $rR = match ($activityLevel) {
            DailyActivityLevel::سبک => 1.2,
            DailyActivityLevel::متوسط => 1.55,
            DailyActivityLevel::شدید,
            DailyActivityLevel::بسیار_شدید => 1.72,
        };
        $bmr = $bmr * $rR;

        $targetCalories = 0;
        switch($dietUser->diet_type_id)
        {
            case 1:
                $reductionRate = match ($activityLevel) {
                    DailyActivityLevel::سبک => 1100,
                    DailyActivityLevel::متوسط => 900,
                    DailyActivityLevel::شدید,
                    DailyActivityLevel::بسیار_شدید => 500,
                };
                $targetCalories = $bmr-$reductionRate;
                break;
            case 3:
                $targetCalories = $bmr;
                break;
            case 2:
                $reductionRate = match ($activityLevel) {
                    DailyActivityLevel::سبک => 500,
                    DailyActivityLevel::متوسط => 900,
                    DailyActivityLevel::شدید,
                    DailyActivityLevel::بسیار_شدید => 1100,
                };
                $targetCalories = $bmr+$reductionRate;
                break;
        }


        // اجرای عملیات در تراکنش
        //DB::transaction(function () use ($request, $targetCalories) {
            $weekly = DietUserWeekly::create([
                'userId' => $request->userId,
                'fromdate' => $request->fromdate,
                'todate' => $todate,
                'weeklyId' => $request->weeklyId,
                'weight' => $request->weight,
                'calories' => $targetCalories,
            ]);


            $calorieData = Calorie::where('dietTypeId' , $dietUser->diet_type_id)->first();
            $weeklyMeals = DietWeeklyMeal::where('diet_weekly_id', $request->weeklyId)->get();
            foreach ($weeklyMeals as $meal)
            {
                $mealType = MealType::from($meal->mealTypeId);   // عدد را تبدیل به enum کن
                $fieldName = lcfirst($mealType->name);           // مثل breakfast, lunch و ...
                $c = $this->getCalorie($calorieData , $fieldName , $targetCalories);
                $mealItems = DietMealItem::where('mealId', $meal->mealId)->get();
                foreach ($mealItems as $item)
                {
                    $ite = DietItem::find($item->itemId);
                    if($ite == null)
                    {
                        echo ($item->itemId);
                        exit;
                    }
                    $cou = 0;
                    if($ite->caloriesGram > 0) {
                        $cou = (($c * $item->percent) / 100) / ($ite->caloriesGram * $ite->weightUnit);
                    }

                    // گرد کردن
                    $unitCount = round($cou * 2) / 2; // پیش‌فرض: گرد به مضرب 0.5
                    if ($ite->atLeast > 0) {
                        //$unitCount = round($cou);
                        $step = $ite->atLeast; // مثلا 1 یا 0.5 یا 0.25
                        $unitCount = round($cou / $step) * $step;

                    }

                    // اطمینان از رعایت حداقل
                    $unitCount = $unitCount < $ite->atLeast ? $ite->atLeast : $unitCount;

                    DietUserWeeklyItem::create([
                        'userWeeklyId' => $weekly->id,
                        'dietWeeklyMealId' => $meal->id,
                        'mealId' => $item->mealId,
                        'mealItemId' => $item->itemId,
                        'calories' => (($c * $item->percent)/100),
                        'unitCount' => $unitCount,
                    ]);
                }

            }
        //});

        return response()->json(['message' => 'ثبت شد.'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/updateWeight",
     *     summary="ویرایش وزن و دریافت اتوماتیک رژیم",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="weight", type="integer", example=85),
     *         )
     *     ),
     *     @OA\Response(response=401, description="عدم دسترسی"),
     *     @OA\Response(response=404, description="کاربر یافت نشد"),
     *     @OA\Response(response=422, description="ورودی نامعتبر یا ناقص")
     * )
     */
    public function updateWeight(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $request->validate([
            'weight' => 'required|numeric',
        ]);
        $weight = $request->weight;
        $weekly = DietWeekly::from('diet_weekly as dw')
        ->where('food_type_id', $user->food_type_id)
        ->join('diet_weekly_types as dwt', 'dw.id', '=', 'dwt.diet_weekly_id')
        ->join('diet_weekly_cultures as dwc', 'dw.id', '=', 'dwc.diet_weekly_id')
        ->where('dwt.type_id', $user->diet_type_id)
        ->where('dwc.food_culture_id', $user->food_culture)
        ->inRandomOrder()
        ->select('dw.*')->first();

        if($weekly != null)
        {
            $dietUser = $user;
            $fromDate = Carbon::today();;
            $todate = $fromDate->copy()->addDays(14)->format('Y-m-d');
            $birthDate = $dietUser->birth_date;
            if (!$birthDate) {
                return response()->json(['message' => 'تاریخ تولد موجود نیست.'], 422);
            }
            $age = Carbon::parse($birthDate)->age;
            $height = $dietUser->height;
            $gender = $dietUser->gender;
            if ($gender === 'male')
            {
                $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
            }
            elseif($gender === 'female')
            {
                $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
            }
            else
            {
                return response()->json(['message' => 'جنسیت نامعتبر است.'], 422);
            }
            $activityLevel = DailyActivityLevel::tryFrom($dietUser->daily_activity_level);
            if (!$activityLevel) {
                return response()->json(['message' => 'سطح فعالیت نامعتبر است.'], 422);
            }
            $rR = match ($activityLevel) {
                DailyActivityLevel::سبک => 1.2,
                DailyActivityLevel::متوسط => 1.55,
                DailyActivityLevel::شدید,
                DailyActivityLevel::بسیار_شدید => 1.72,
            };
            $bmr = $bmr * $rR;
            //switch($dietUser->diet_type_id)
            switch($user->diet_type_id)
            {
                case 1:
                    $reductionRate = match ($activityLevel) {
                        DailyActivityLevel::سبک => 1100,
                        DailyActivityLevel::متوسط => 900,
                        DailyActivityLevel::شدید,
                        DailyActivityLevel::بسیار_شدید => 500,
                    };
                    $targetCalories = $bmr-$reductionRate;
                    break;
                case 3:
                    $targetCalories = $bmr;
                    break;
                case 2:
                    $reductionRate = match ($activityLevel) {
                        DailyActivityLevel::سبک => 500,
                        DailyActivityLevel::متوسط => 900,
                        DailyActivityLevel::شدید,
                        DailyActivityLevel::بسیار_شدید => 1100,
                    };
                    $targetCalories = $bmr + $reductionRate;
                    break;
            }
            $weekly2 = DietUserWeekly::create([
                'userId' => $dietUser->id,
                'fromdate' => $fromDate,
                'todate' => $todate,
                'weeklyId' => $weekly->id,
                'weight' => $weight,
                'calories' => $targetCalories,
            ]);

            $calorieData = Calorie::where('dietTypeId' , $dietUser->diet_type_id)->first();
            $weeklyMeals = DietWeeklyMeal::where('diet_weekly_id', $weekly->id)->get();
            foreach ($weeklyMeals as $meal)
            {
                $mealType = MealType::from($meal->mealTypeId);   // عدد را تبدیل به enum کن
                $fieldName = lcfirst($mealType->name);           // مثل breakfast, lunch و ...
                $c = $this->getCalorie($calorieData , $fieldName , $targetCalories);
                $mealItems = DietMealItem::where('mealId', $meal->mealId)->get();
                foreach ($mealItems as $item)
                {
                    $ite = DietItem::find($item->itemId);
                    if($ite == null)
                    {
                        echo ($item->itemId);
                        exit;
                    }
                    $cou = 0;
                    if($ite->caloriesGram > 0) {
                        $cou = (($c * $item->percent) / 100) / ($ite->caloriesGram * $ite->weightUnit);
                    }

                    // گرد کردن
                    $unitCount = round($cou * 2) / 2; // پیش‌فرض: گرد به مضرب 0.5
                    if ($ite->atLeast == 1) {
                        //$unitCount = round($cou);
                        $step = $ite->atLeast; // مثلا 1 یا 0.5 یا 0.25
                        $unitCount = round($cou / $step) * $step;
                    }

                    // اطمینان از رعایت حداقل
                    $unitCount = $unitCount < $ite->atLeast ? $ite->atLeast : $unitCount;

                    DietUserWeeklyItem::create([
                        'userWeeklyId' => $weekly2->id,
                        'dietWeeklyMealId' => $meal->id,
                        'mealId' => $item->mealId,
                        'mealItemId' => $item->itemId,
                        'calories' => (($c * $item->percent)/100),
                        'unitCount' => $unitCount,
                    ]);

                }

            }
        }

        return response()->json(['message' => 'ثبت شد.'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/addDiet",
     *     summary="افزودن رژیم هفتگی جدید برای کاربر",
     *     description="این سرویس با توجه به نوع رژیم، فرهنگ غذایی، فعالیت و اطلاعات کاربر، برنامه هفتگی تغذیه اختصاصی ایجاد می‌کند.",
     *     tags={"Diet"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"weight"},
     *             @OA\Property(property="weight", type="integer", example=85, description="وزن فعلی کاربر جهت محاسبه کالری")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="رژیم با موفقیت ثبت شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="ثبت شد.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="توکن نامعتبر یا کاربر لاگین نیست.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="دسترسی غیرمجاز.")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="ورودی نامعتبر یا کمبود اطلاعات کاربر",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="تاریخ تولد موجود نیست.")
     *         )
     *     )
     * )
     */
    public function addDiet(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $data = $request->validate([
            'weight' => 'required|integer',
        ]);

        $week = DietWeekly::from('diet_weekly as dw')
        ->where('food_type_id', $user->food_type_id)
        ->join('diet_weekly_types as dwt', 'dw.id', '=', 'dwt.diet_weekly_id')
        ->join('diet_weekly_cultures as dwc', 'dw.id', '=', 'dwc.diet_weekly_id')
        ->where('dwt.type_id', $user->diet_type_id)
        ->where('dwc.food_culture_id', $user->food_culture)
        ->inRandomOrder()
        ->select('dw.*')->first();

        if($week != null)
        {
            $fromDate = Carbon::today();
            $todate = $fromDate->copy()->addDays(7)->format('Y-m-d');


            $dietUser = $user;

            $birthDate = $dietUser->birth_date;
            if (!$birthDate) {
                return response()->json(['message' => 'تاریخ تولد موجود نیست.'], 422);
            }

            $age = Carbon::parse($birthDate)->age;
            $weight = $request->weight;
            $height = $dietUser->height;
            $gender = $dietUser->gender;

            if ($gender === 'male') {
                $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
            } elseif ($gender === 'female') {
                $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
            } else {
                return response()->json(['message' => 'جنسیت نامعتبر است.'], 422);
            }


            $activityLevel = DailyActivityLevel::tryFrom($dietUser->daily_activity_level);
            if (!$activityLevel) {
                return response()->json(['message' => 'سطح فعالیت نامعتبر است.'], 422);
            }
            $rR = match ($activityLevel) {
                DailyActivityLevel::سبک => 1.2,
                DailyActivityLevel::متوسط => 1.55,
                DailyActivityLevel::شدید,
                DailyActivityLevel::بسیار_شدید => 1.72,
            };
            $bmr = $bmr * $rR;

            $targetCalories = 0;
            switch($dietUser->diet_type_id)
            {
                case 1:
                    $reductionRate = match ($activityLevel) {
                        DailyActivityLevel::سبک => 1100,
                        DailyActivityLevel::متوسط => 900,
                        DailyActivityLevel::شدید,
                        DailyActivityLevel::بسیار_شدید => 500,
                    };
                    $targetCalories = $bmr-$reductionRate;
                    break;
                case 3:
                    $targetCalories = $bmr;
                    break;
                case 2:
                    $reductionRate = match ($activityLevel) {
                        DailyActivityLevel::سبک => 500,
                        DailyActivityLevel::متوسط => 900,
                        DailyActivityLevel::شدید,
                        DailyActivityLevel::بسیار_شدید => 1100,
                    };
                    $targetCalories = $bmr+$reductionRate;
                    break;
            }


            // اجرای عملیات در تراکنش

            $weekly = DietUserWeekly::create([
                'userId' => $user->id,
                'fromdate' => $fromDate,
                'todate' => $todate,
                'weeklyId' => $week->id,
                'weight' => $request->weight,
                'calories' => $targetCalories,
            ]);


            $calorieData = Calorie::where('dietTypeId' , $dietUser->diet_type_id)->first();
            $weeklyMeals = DietWeeklyMeal::where('diet_weekly_id', $week->id)->get();
            foreach ($weeklyMeals as $meal)
            {
                $mealType = MealType::from($meal->mealTypeId);   // عدد را تبدیل به enum کن
                $fieldName = lcfirst($mealType->name);           // مثل breakfast, lunch و ...
                $c = $this->getCalorie($calorieData , $fieldName , $targetCalories);
                $mealItems = DietMealItem::where('mealId', $meal->mealId)->get();
                foreach ($mealItems as $item)
                {
                    $ite = DietItem::find($item->itemId);
                    if($ite == null)
                    {
                        echo ($item->itemId);
                        exit;
                    }
                    $cou = 0;
                    if($ite->caloriesGram > 0) {
                        $cou = (($c * $item->percent) / 100) / ($ite->caloriesGram * $ite->weightUnit);
                    }

                    // گرد کردن
                    $unitCount = round($cou * 2) / 2; // پیش‌فرض: گرد به مضرب 0.5
                    if ($ite->atLeast > 0) {
                        //$unitCount = round($cou);
                        $step = $ite->atLeast; // مثلا 1 یا 0.5 یا 0.25
                        $unitCount = round($cou / $step) * $step;

                    }

                    // اطمینان از رعایت حداقل
                    $unitCount = $unitCount < $ite->atLeast ? $ite->atLeast : $unitCount;

                    DietUserWeeklyItem::create([
                        'userWeeklyId' => $weekly->id,
                        'dietWeeklyMealId' => $meal->id,
                        'mealId' => $item->mealId,
                        'mealItemId' => $item->itemId,
                        'calories' => (($c * $item->percent)/100),
                        'unitCount' => $unitCount,
                    ]);
                }

            }

        }
        return response()->json(['message' => 'ثبت شد.'], 201);
    }


    /**
     * @OA\Put(
     *     path="/api/user-weekly/{id}",
     *     summary="ویرایش برنامه رژیم هفتگی کاربر",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه رکورد رژیم هفتگی",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"userId", "fromdate", "todate", "weeklyId"},
     *             @OA\Property(property="userId", type="integer", example=5),
     *             @OA\Property(property="fromdate", type="string", format="date", example="2025-07-01"),
     *             @OA\Property(property="weeklyId", type="integer", example=2),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ویرایش با موفقیت انجام شد",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="ویرایش شد."))
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="عدم دسترسی"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="کاربر یا رژیم یافت نشد"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی یا داده نامعتبر"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $weekly = DietUserWeekly::find($id);
        if (!$weekly) return response()->json(['message' => 'یافت نشد.'], 404);

        $request->validate([
            'userId' => 'required|integer',
            'fromdate' => 'required|date',
            'weeklyId' => 'required|integer',
            'weight' => 'required|integer',
        ]);
        $fromDate = Carbon::parse($request->fromdate);
        $todate = $fromDate->copy()->addDays(7)->format('Y-m-d');

        // حذف آیتم‌های قبلی کاربر
        DietUserWeeklyItem::where('userWeeklyId', $id)->delete();

        // دریافت اطلاعات کاربر رژیم
        $dietUser = User::find($request->userId);
        if (!$dietUser) {
            return response()->json(['message' => 'کاربر یافت نشد.'], 404);
        }

        $birthDate = $dietUser->birth_date;
        if (!$birthDate) {
            return response()->json(['message' => 'تاریخ تولد موجود نیست.'], 422);
        }

        $age = Carbon::parse($birthDate)->age;
        $weight = $dietUser->weight;
        $height = $dietUser->height;
        $gender = $dietUser->gender;

        if ($gender === 'male') {
            $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
        } elseif ($gender === 'female') {
            $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
        } else {
            return response()->json(['message' => 'جنسیت نامعتبر است.'], 422);
        }

        // سطح فعالیت و کاهش کالری
        $activityLevel = DailyActivityLevel::tryFrom($dietUser->daily_activity_level);
        if (!$activityLevel) {
            return response()->json(['message' => 'سطح فعالیت نامعتبر است.'], 422);
        }
        $rR = match ($activityLevel) {
            DailyActivityLevel::سبک => 1.2,
            DailyActivityLevel::متوسط => 1.55,
            DailyActivityLevel::شدید,
            DailyActivityLevel::بسیار_شدید => 1.72,
        };
        $bmr = $bmr * $rR;
        /*$bmi = $weight / (($weight/100) * ($weight/100));
        if($bmi < 18)
        {
            $weight_goal_id = 2;
        }
        elseif($bmi > 24)
        {
            $weight_goal_id = 1;
        }
        else
        {
            $weight_goal_id = 3;
        }*/


        //switch($weight_goal_id)
        switch($dietUser->diet_type_id)
        {
            case 1:
                $reductionRate = match ($activityLevel) {
                    DailyActivityLevel::سبک => 1100,
                    DailyActivityLevel::متوسط => 900,
                    DailyActivityLevel::شدید,
                    DailyActivityLevel::بسیار_شدید => 500,
                };
                $targetCalories = $bmr-$reductionRate;
                break;
            case 3:
                $targetCalories = $bmr;
                break;
            case 2:
                $reductionRate = match ($activityLevel) {
                    DailyActivityLevel::سبک => 500,
                    DailyActivityLevel::متوسط => 900,
                    DailyActivityLevel::شدید,
                    DailyActivityLevel::بسیار_شدید => 1100,
                };
                $targetCalories = $bmr+$reductionRate;
                break;
        }

        // به‌روزرسانی رژیم کاربر
        $weekly->update([
            'userId' => $request->userId,
            'fromdate' => $request->fromdate,
            'todate' => $todate,
            'weeklyId' => $request->weeklyId,
            'calories' => $targetCalories,
        ]);


        $calorieData = Calorie::where('dietTypeId' , $dietUser->diet_type_id)->first();
        $weeklyMeals = DietWeeklyMeal::where('diet_weekly_id', $request->weeklyId)->get();
        foreach ($weeklyMeals as $meal)
        {
            $mealType = MealType::from($meal->mealTypeId);   // عدد را تبدیل به enum کن
            $fieldName = lcfirst($mealType->name);           // مثل breakfast, lunch و ...
            //$c = $calorieData->$fieldName ?? null;
            $c = $this->getCalorie($calorieData , $fieldName , $targetCalories);
            $mealItems = DietMealItem::where('mealId', $meal->mealId)->get();

            foreach ($mealItems as $item) {
                $ite = DietItem::find($item->itemId);

                $cou = 0;
                if($ite->caloriesGram > 0) {
                    $cou = (($c * $item->percent) / 100) / ($ite->caloriesGram * $ite->weightUnit);
                }

                // گرد کردن
                $unitCount = round($cou * 2) / 2; // پیش‌فرض: گرد به مضرب 0.5
                if ($ite->atLeast > 0) {
                    //$unitCount = round($cou);
                    $step = $ite->atLeast; // مثلا 1 یا 0.5 یا 0.25
                    $unitCount = round($cou / $step) * $step;
                }

                // اطمینان از رعایت حداقل
                $unitCount = $unitCount < $ite->atLeast ? $ite->atLeast : $unitCount;

                DietUserWeeklyItem::create([
                    'userWeeklyId' => $weekly->id,
                    'dietWeeklyMealId' => $meal->id,
                    'mealId' => $item->mealId,
                    'mealItemId' => $item->itemId,
                    'calories' => (($c * $item->percent)/100),
                    'unitCount' => $unitCount,
                ]);
            }
        }

        return response()->json(['message' => 'ویرایش شد.']);
    }

    /**
     * @OA\Post(
     *     path="/api/diet/updateWeekly",
     *     summary="به‌روزرسانی برنامه هفتگی رژیم کاربر",
     *     description="این سرویس یک هفته جدید از برنامه رژیم کاربر ایجاد و جایگزین هفته قبلی می‌کند و بر اساس اطلاعات کاربر کالری‌ها و وعده‌ها را محاسبه می‌کند.",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dietUserWeeklyId", "mealId", "day", "mealTypeLabel"},
     *             @OA\Property(property="dietUserWeeklyId", type="integer", example=12, description="آیدی جدول diet_user_weekly"),
     *             @OA\Property(property="mealId", type="integer", example=3, description="آیدی وعده غذایی"),
     *             @OA\Property(property="day", type="integer", example=2, description="شماره روز هفته (مثلاً 1=شنبه)"),
     *             @OA\Property(property="mealTypeLabel", type="string", example="breakfast", description="برچسب نوع وعده غذایی"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="عملیات موفقیت‌آمیز",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="ویرایش شد."),
     *             @OA\Property(property="new_weekly_id", type="integer", example=15)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="دسترسی غیرمجاز.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="موردی یافت نشد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="یافت نشد.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی یا داده نامعتبر",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="تاریخ تولد موجود نیست.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="خطا در پردازش درخواست",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="خطا در کپی داده‌ها"),
     *             @OA\Property(property="error", type="string", example="Database error...")
     *         )
     *     )
     * )
     */

    public function updateWeekly(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }

        $request->validate([
            'dietUserWeeklyId' => 'required|integer',
            'mealId' => 'required|integer',
            'day' => 'required|integer',
            'mealTypeLabel' => 'required|string',
        ]);

        // پیدا کردن رکورد diet_user_weekly
        $dietUserWeekly = DietUserWeekly::find($request->dietUserWeeklyId);
        if (!$dietUserWeekly) {
            return response()->json(['message' => 'یافت نشد.'], 404);
        }

        $weekly = $dietUserWeekly->weekly;
        if (!$weekly) {
            return response()->json(['message' => 'هفته مرتبط پیدا نشد.'], 404);
        }

        DB::beginTransaction();
        try {
            // 1- کپی خود weekly
            $newWeekly = $weekly->replicate();
            $newWeekly->type = 'user';
            $newWeekly->save();

            // 2- کپی meals
            foreach ($weekly->meals as $meal) {
                $mealTypeId = MealType::fromLabel($request->mealTypeLabel);

                $newMeal = $meal->replicate();
                $newMeal->diet_weekly_id = $newWeekly->id;
                //$newMeal->day = $request->day;
                //$newMeal->mealTypeId = $mealTypeId;
                $newMeal->save();
                $newMeal = $meal->replicate();
                DietWeeklyMeal::where('diet_weekly_id' ,  $newWeekly->id)->where('day' ,  $request->day)
                ->where('mealTypeId' , $mealTypeId)
                ->update([
                'mealId' => $request->mealId,
                ]);
            }

            // 3- کپی cultures
            foreach ($weekly->cultures as $culture) {
                $newCulture = $culture->replicate();
                $newCulture->diet_weekly_id = $newWeekly->id;
                $newCulture->save();
            }

            // 4- کپی types
            foreach ($weekly->types as $type) {
                $newType = $type->replicate();
                $newType->diet_weekly_id = $newWeekly->id;
                $newType->save();
            }

            // 5- آپدیت آیدی هفته در diet_user_weekly
            $dietUserWeekly->weeklyId = $newWeekly->id;
            $dietUserWeekly->save();

            DB::commit();

            // return response()->json([
            //     'message' => 'ویرایش شد.',
            //     'new_weekly_id' => $newWeekly->id
            // ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطا در کپی داده‌ها', 'error' => $e->getMessage()], 500);
        }
        $weekly = DietUserWeekly::find($request->dietUserWeeklyId);
        if (!$weekly) return response()->json(['message' => 'یافت نشد.'], 404);

        // حذف آیتم‌های قبلی کاربر
        DietUserWeeklyItem::where('userWeeklyId', $request->dietUserWeeklyId)->delete();
        //exit;
        // دریافت اطلاعات کاربر رژیم
        $dietUser = User::find($user->id);
        if (!$dietUser) {
            return response()->json(['message' => 'کاربر یافت نشد.'], 404);
        }

        $birthDate = $dietUser->birth_date;
        if (!$birthDate) {
            return response()->json(['message' => 'تاریخ تولد موجود نیست.'], 422);
        }

        $age = Carbon::parse($birthDate)->age;
        $weight = $dietUser->weight;
        $height = $dietUser->height;
        $gender = $dietUser->gender;

        if ($gender === 'male') {
            $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
        } elseif ($gender === 'female') {
            $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
        } else {
            return response()->json(['message' => 'جنسیت نامعتبر است.'], 422);
        }

        // سطح فعالیت و کاهش کالری
        $activityLevel = DailyActivityLevel::tryFrom($dietUser->daily_activity_level);
        if (!$activityLevel) {
            return response()->json(['message' => 'سطح فعالیت نامعتبر است.'], 422);
        }
        $rR = match ($activityLevel) {
            DailyActivityLevel::سبک => 1.2,
            DailyActivityLevel::متوسط => 1.55,
            DailyActivityLevel::شدید,
            DailyActivityLevel::بسیار_شدید => 1.72,
        };
        $bmr = $bmr * $rR;

        /*$bmi = $weight / (($weight/100) * ($weight/100));
        if($bmi < 18)
        {
            $weight_goal_id = 2;
        }
        elseif($bmi > 24)
        {
            $weight_goal_id = 1;
        }
        else
        {
            $weight_goal_id = 3;
        }*/


        //switch($weight_goal_id)
        switch($dietUser->diet_type_id)
        {
            case 1:
                $reductionRate = match ($activityLevel) {
                    DailyActivityLevel::سبک => 1100,
                    DailyActivityLevel::متوسط => 900,
                    DailyActivityLevel::شدید,
                    DailyActivityLevel::بسیار_شدید => 500,
                };
                $targetCalories = $bmr-$reductionRate;
                break;
            case 2:
                $targetCalories = $bmr;
                break;
            case 3:
                $reductionRate = match ($activityLevel) {
                    DailyActivityLevel::سبک => 500,
                    DailyActivityLevel::متوسط => 900,
                    DailyActivityLevel::شدید,
                    DailyActivityLevel::بسیار_شدید => 1100,
                };
                $targetCalories = $bmr+$reductionRate;
                break;
        }

        // به‌روزرسانی رژیم کاربر
        $weekly->update([
            'calories' => $targetCalories,
        ]);


        $calorieData = Calorie::where('dietTypeId' , $dietUser->diet_type_id)->first();
        $weeklyMeals = DietWeeklyMeal::where('diet_weekly_id', $newWeekly->id)->get();
        foreach ($weeklyMeals as $meal) {
            $mealType = MealType::from($meal->mealTypeId);   // عدد را تبدیل به enum کن
            $fieldName = lcfirst($mealType->name);           // مثل breakfast, lunch و ...
            //$c = $calorieData->$fieldName ?? null;
            $c = $this->getCalorie($calorieData , $fieldName , $targetCalories);
            $mealItems = DietMealItem::where('mealId', $meal->mealId)->get();

            foreach ($mealItems as $item) {
                $ite = DietItem::find($item->itemId);

                $cou = 0;
                if($ite->caloriesGram > 0) {
                    $cou = (($c * $item->percent) / 100) / ($ite->caloriesGram * $ite->weightUnit);
                }

                // گرد کردن
                $unitCount = round($cou * 2) / 2; // پیش‌فرض: گرد به مضرب 0.5
                if ($ite->atLeast > 0) {
                    //$unitCount = round($cou);
                    $step = $ite->atLeast; // مثلا 1 یا 0.5 یا 0.25
                    $unitCount = round($cou / $step) * $step;
                }

                // اطمینان از رعایت حداقل
                $unitCount = $unitCount < $ite->atLeast ? $ite->atLeast : $unitCount;

                DietUserWeeklyItem::create([
                    'userWeeklyId' => $weekly->id,
                    'dietWeeklyMealId' => $meal->id,
                    'mealId' => $item->mealId,
                    'mealItemId' => $item->itemId,
                    'calories' => (($c * $item->percent)/100),
                    'unitCount' => $unitCount,
                ]);
            }
        }
    }


    /**
     * @OA\Get(
     *     path="/api/user-weekly",
     *     summary="لیست برنامه‌های رژیم هفتگی کاربران",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="query",
     *         required=false,
     *         description="فیلتر بر اساس شناسه کاربر",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         required=false,
     *         description="تعداد آیتم‌ها در هر صفحه (پیش‌فرض: 20)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="شماره صفحه",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="لیست با موفقیت دریافت شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=12),
     *                 @OA\Property(property="userId", type="integer", example=5),
     *                 @OA\Property(property="user", type="string", example="حسین گلاب"),
     *                 @OA\Property(property="calories", type="integer", example=2150),
     *                 @OA\Property(property="fromdate", type="string", format="date", example="2025-07-01"),
     *                 @OA\Property(property="todate", type="string", format="date", example="2025-07-07"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-07-03T08:30:00")
     *             )),
     *             @OA\Property(property="totalCount", type="integer", example=42)
     *         )
     *     ),
     *     @OA\Response(response=401, description="عدم دسترسی")
     * )
     */

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pageSize = $request->pagesize ?? 20;
        $query = DietUserWeekly::query();

        if ($request->filled('userId')) {
            $query->where('userId', $request->userId);
        }

        $total = $query->count();
        $data = $query->orderBy('id', 'desc')->paginate($pageSize);
        $data = array_map(function ($item) {
            $user = User::find($item->userId);
            return [
                'id' => $item->id,
                'userId' => $item->userId,
                'user' => $user != null ? $user->first_name.' '.$user->last_name : null,
                'calories' => $item->calories,
                'fromdate' => $item->fromdate,
                'todate' => $item->todate,
                'created_at' => $item->created_at,
                'weeklyId' => $item->weeklyId,
                'weekly' => $item->weekly != null ? $item->weekly->name : null,
            ];
        }, $data->items());

        return response()->json([
            'result' => $data,
            'totalCount' => $total
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user-weekly/{id}",
     *     summary="جزئیات برنامه هفتگی کاربر",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="موفق")
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $weekly = DietUserWeekly::with(['user', 'weekly', 'items.mealItem', 'items.dietWeeklyMeal'])->find($id);

        if (!$weekly) {
            return response()->json(['message' => 'یافت نشد.'], 404);
        }

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

        $output = [
            'id' => $weekly->id,
            'userId' => $weekly->userId,
            'userName' => trim("{$weekly->user?->first_name} {$weekly->user?->last_name}"),
            'fromdate' => $weekly->fromdate,
            'todate' => $weekly->todate,
            'food_type_id' => $weekly->food_type_id,
            'food_type' => $weekly->food_type_id ? FoodType::from($weekly->food_type_id)->label() : null,
            'weeklyId' => $weekly->weeklyId,
            'weekly' => $weekly->weekly?->name,
            'calories' => $weekly->calories,
            'created_at' => $weekly->created_at,
            'updated_at' => $weekly->updated_at,
            'items' => [],
        ];

        foreach ($weekly->items as $item) {
            $day = $item->dietWeeklyMeal?->day;
            $mealTypeId = $item->dietWeeklyMeal?->mealTypeId;
            $mealLabel = $mealTypeId ? MealType::from($mealTypeId)->label() : null;

            if ($day && $mealLabel) {
                $output['items'][$day][$mealLabel][] = [
                    'mealItemId' => $item->mealItemId,
                    'itemTitle' => $item->mealItem?->name,
                    'unit' => $item->mealItem?->unit,
                    'unitCount' => $item->unitCount,
                ];
            }
        }

        // مرتب‌سازی وعده‌ها بر اساس mealOrder
        foreach ($output['items'] as $day => $meals) {
            uksort($meals, function ($a, $b) use ($mealOrder) {
                $posA = array_search($a, $mealOrder);
                $posB = array_search($b, $mealOrder);
                return ($posA === false ? PHP_INT_MAX : $posA) <=> ($posB === false ? PHP_INT_MAX : $posB);
            });

            $output['items'][$day] = $meals;
        }

        return response()->json($output);
    }

    /**
     * @OA\Get(
     *     path="/api/last-user-weekly-items",
     *     operationId="getLastUserWeeklyItems",
     *     tags={"Diet Weekly"},
     *     summary="دریافت آخرین برنامه هفتگی کاربر",
     *     description="آخرین برنامه غذایی هفتگی ثبت‌شده برای کاربر لاگین‌شده را به تفکیک روز و وعده غذایی برمی‌گرداند.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="دریافت موفق اطلاعات برنامه هفتگی",
     *         @OA\JsonContent(
     *             type="object",
     *             additionalProperties={
     *                 "type":"object",
     *                 "additionalProperties":{
     *                     "type":"array",
     *                     "items":{
     *                         "type":"object",
     *                         @OA\Property(property="mealId", type="integer", example=12),
     *                         @OA\Property(property="mealItemId", type="integer", example=45),
     *                         @OA\Property(property="itemTitle", type="string", example="تخم مرغ"),
     *                         @OA\Property(property="unit", type="string", example="عدد"),
     *                         @OA\Property(property="unitCount", type="number", example=2)
     *                     }
     *                 }
     *             }
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="کاربر لاگین نشده است",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="دسترسی غیرمجاز.")
     *         )
     *     )
     * )
     */

    public function lastUserWeeklyItems()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }

        $item = User::find($user->id);

        if($item == null)
        {
            return response()->json([],200);
        }
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

            foreach ($latestPlan->items as $planItem)
            {
                $day = $planItem->dietWeeklyMeal?->day;
                if (!$day) {
                    continue;
                }
                $mealTypeLabel = $planItem->dietWeeklyMeal?->mealTypeId
                    ? MealType::from($planItem->dietWeeklyMeal->mealTypeId)->label()
                    : null;
                $latestPlanOutput['items'][$day][$mealTypeLabel][] = [
                    'mealId' => $planItem->mealId,
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
        return response()->json([
            $latestPlanOutput,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/last-user-weekly",
     *     summary="دریافت آخرین برنامه هفتگی کاربر",
     *     description="این متد اطلاعات کامل کاربر و آخرین برنامه غذایی هفتگی او را برمی‌گرداند. نیاز به احراز هویت دارد.",
     *     tags={"Diet"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="اطلاعات کاربر و آخرین برنامه غذایی",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=12),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="first_name", type="string", example="Ali"),
     *             @OA\Property(property="last_name", type="string", example="Ahmadi"),
     *             @OA\Property(property="age", type="integer", example=32),
     *             @OA\Property(property="height", type="number", format="float", example=175),
     *             @OA\Property(property="weight", type="number", format="float", example=80.5),
     *             @OA\Property(property="diet_type", type="string", example="کاهش وزن"),
     *             @OA\Property(
     *                 property="latest_diet_plan",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="fromdate", type="string", example="2025-08-01"),
     *                 @OA\Property(property="todate", type="string", example="2025-08-07"),
     *                 @OA\Property(property="weekly", type="string", example="برنامه هفتگی شماره 3"),
     *                 @OA\Property(property="items", type="object", example={
     *                     "شنبه": {
     *                         "صبحانه": {
     *                             {
     *                                 "mealItemId": 1,
     *                                 "itemTitle": "نان سبوس‌دار",
     *                                 "unit": "برش",
     *                                 "unitCount": 2
     *                             }
     *                         }
     *                     }
     *                 })
     *             ),
     *             @OA\Property(
     *                 property="weights",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="weight", type="number", format="float", example=80),
     *                     @OA\Property(property="date", type="string", example="2025-08-01"),
     *                     @OA\Property(property="type", type="string", example="weekly")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     )
     * )
     */

    public function lastUserWeekly()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }

        $item = User::find($user->id);

        if($item == null)
        {
            return response()->json([],200);
        }
        $age = $item->birth_date ? Carbon::parse($item->birth_date)->age : null;
        $dietTypeLabel = $item->diet_type_id ? DietType::from($item->diet_type_id)->label() : null;

        $latestPlan = $item->dietUserWeeklies()->with(['weekly', 'items.mealItem', 'items.dietWeeklyMeal', 'items.meal', 'items.meal.image'])->orderByDesc('id')->first();
        $subscriptionDay = $item->expire_at ? Carbon::parse($item->expire_at)->diffInDays(Carbon::today(), false) : null;
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

            foreach ($latestPlan->items as $planItem)
            {
                $day = $planItem->dietWeeklyMeal?->day;
                if (!$day) {
                    continue;
                }
                $mealTypeLabel = $planItem->dietWeeklyMeal?->mealTypeId
                    ? MealType::from($planItem->dietWeeklyMeal->mealTypeId)->label()
                    : null;
                $latestPlanOutput['items'][$day][$mealTypeLabel][] = [
                    'mealId' => $planItem->mealId,
                    'imageUrl' => $planItem->meal?->image?->url(),
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
        $planOneDate = Subscription::where('status' , 'active')->where('user_id' , $item->id)->first();

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
            'wrist_size' => $item->wrist_size,
            'pregnancy_week' => $item->pregnancy_week,
            'country_id' => $item->country_id,
            'state_id' => $item->state_id,
            'city_id' => $item->city_id,
            'postal_code' => $item->postal_code,
            'address' => $item->address,
            'diet_type_id' => $item->diet_type_id,
            'diet_type' => $dietTypeLabel,
            'daily_activity_level' => $item->daily_activity_level,
            'diet_goal' => $item->diet_goal,
            'has_diet_history' => $item->has_diet_history,
            'diet_history' => $item->diet_history,
            'package' => $item->package,
            'created_at' => $item->created_at,
            'latest_diet_plan' => $latestPlanOutput,
            'weights' => $weights,
            'planOneDate' => $planOneDate != null,
            'subscription_day' => $subscriptionDay,
        ]);
    }






    /**
     * @OA\Delete(
     *     path="/api/user-weekly/{id}",
     *     summary="حذف برنامه رژیم هفتگی",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="حذف شد")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        DietUserWeekly::where('id', $id)->delete();
        DietUserWeeklyItem::where('userWeeklyId', $id)->delete();

        return response()->json(['message' => 'حذف شد.']);
    }

    /**
     * @OA\Get(
     *     path="/api/food-types",
     *     summary="دریافت لیست انواع غذا (عدد و نام)",
     *     tags={"FoodType"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست انواع غذا",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="label", type="string", example="وجبات عادية")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function foodTypes()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 401);
        }

        return response()->json(FoodType::getList());
    }
}
