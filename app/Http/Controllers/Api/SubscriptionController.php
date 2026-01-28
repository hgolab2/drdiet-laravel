<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietWeekly;
use Illuminate\Support\Facades\Auth;
use App\Models\Subscription;
use App\Models\DietUserWeeklyItem;
use App\Models\DietItem;
use App\Models\User;
use App\Models\DietUserWeekly;
use App\Models\Calorie;
use App\Models\DietWeeklyMeal;
use App\Models\DietMealItem;
use App\Enums\MealType;
use App\Enums\DailyActivityLevel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    function getQuery($item){
        $query = str_replace(array('?'), array('\'%s\''), $item->toSql());
        return $query = vsprintf($query, $item->getBindings());
            //echo($query);
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
     *     path="/api/addSubscriptions",
     *     summary="ایجاد اشتراک جدید",
     *     description="این متد یک اشتراک جدید ایجاد می‌کند.",
     *     tags={"Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","plan_id"},
     *             @OA\Property(property="plan_id", type="integer"),
     *             @OA\Property(property="price", type="number", format="float", nullable=true),
     *             @OA\Property(property="start_date", type="string", format="date", nullable=true),
     *         )
     *     ),
     *     @OA\Response(response=201, description="اشتراک ایجاد شد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function addSubscriptions(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $data = $request->validate([
            'plan_id'    => 'nullable|numeric',
            'price'      => 'nullable|numeric',
            'start_date' => 'nullable|date',
        ]);
        $data['user_id'] = $user->id;
        if($data['plan_id'] == 1)
        {
            $data['status'] = 'active';
            $planOneDate = Subscription::where('status' , 'active')->where('user_id' , $user->id)->first();

            if($planOneDate == null)
            {
                /*$bmi = $user->weight / (($user->height/100) * ($user->height/100));

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
                    $birthDate = $dietUser->birth_date;
                    if (!$birthDate) {
                        return response()->json(['message' => 'تاریخ تولد موجود نیست.'], 422);
                    }
                    $age = Carbon::parse($birthDate)->age;
                    $weight = $dietUser->weight;
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
                        'fromdate' => $request->start_date,
                        'todate' => $request->start_date,
                        'weeklyId' => $weekly->id,
                        'weight' => $dietUser->weight,
                        'calories' => $targetCalories,
                    ]);

                    $calorieData = Calorie::where('dietTypeId' , $dietUser->diet_type_id)->first();
                    $weeklyMeals = DietWeeklyMeal::where('diet_weekly_id', $weekly->id)->where('day' , 1)->get();
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
                            // بروزرسانی expire_at در DietUser
                            /*if ($dietUser->expire_at) {
                                $dietUser->expire_at = Carbon::parse($dietUser->expire_at)->addDay();
                            } else {*/
                                $dietUser->expire_at = Carbon::tomorrow(); // اگر null بود فردا
                            //}
                            $dietUser->save();
                        }

                    }
                }
            }
        }
        else
        {
            $data['status'] = 'pending';
        }
        $calorie = Subscription::create($data);
        return response()->json($calorie, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/subscriptions",
     *     summary="لیست اشتراک‌ها",
     *     description="این متد لیست اشتراک‌ها را با امکان فیلتر بر اساس user_id، plan_id و status بازمی‌گرداند.",
     *     tags={"Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="شناسه کاربر",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="plan_id",
     *         in="query",
     *         description="شناسه پلن",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="وضعیت اشتراک (active, expired, cancelled, pending)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد رکورد در هر صفحه",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="لیست اشتراک‌ها",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="user_id", type="integer"),
     *                     @OA\Property(property="user", type="string", nullable=true),
     *                     @OA\Property(property="plan_id", type="integer"),
     *                     @OA\Property(property="price", type="number", format="float", nullable=true),
     *                     @OA\Property(property="payment_id", type="string", nullable=true),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="start_date", type="string", format="date", nullable=true),
     *                 )
     *             ),
     *             @OA\Property(property="totalCount", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->is_superuser) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $pageSize = (int)($request->pagesize ?? 20);
        $query = Subscription::query();

        if ($request->filled('user_id')) {
            $query->where('user_id',  $request->user_id);
        }
        if ($request->filled('plan_id')) {
            $query->where('plan_id',  $request->plan_id);
        }
        if ($request->filled('status')) {
            $query->where('status',  $request->status);
        }

        $totalCount = $query->count();
        $items = $query->orderBy('id', 'desc')->paginate($pageSize);
        $items = array_map(function ($item) {
            return [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'user' => $item->user != null ? $item->user->first_name.' '.$item->user->last_name : null,
                'plan_id' => $item->plan_id,
                'price' => $item->price,
                'payment_id' => $item->payment_id,
                'status' => $item->status,
                'start_date' => $item->start_date,
            ];
        }, $items->items());

        return response()->json([
            'result' => $items,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/subscriptions",
     *     summary="ایجاد اشتراک جدید",
     *     description="این متد یک اشتراک جدید ایجاد می‌کند.",
     *     tags={"Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","plan_id"},
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="plan_id", type="integer"),
     *             @OA\Property(property="price", type="number", format="float", nullable=true),
     *             @OA\Property(property="payment_id", type="string", maxLength=100, nullable=true),
     *             @OA\Property(property="status", type="string", enum={"active","expired","cancelled","pending"}, nullable=true),
     *             @OA\Property(property="start_date", type="string", format="date", nullable=true),
     *         )
     *     ),
     *     @OA\Response(response=201, description="اشتراک ایجاد شد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $data = $request->validate([
            'user_id'    => 'required|exists:diet_users,id',
            'plan_id'    => 'nullable|numeric',
            'price'      => 'nullable|numeric',
            'payment_id' => 'nullable|string|max:100',
            'status'     => 'nullable|in:active,expired,cancelled,pending',
            'start_date' => 'nullable|date',
        ]);
        if ($data['plan_id']>0) {
            $calorie = Subscription::create($data);
        }
        // پیدا کردن کاربر مربوطه
        $dietUser = User::find($data['user_id']);
        if ($dietUser /*&& $data['plan_id']*/ && $data['status']=='active') {

            if ($data['plan_id']>0) {
                $days = $data['plan_id'];

                $expireAt = $dietUser->expire_at;

                // اگر قبلاً تاریخ داشت و هنوز منقضی نشده
                if ($expireAt && Carbon::parse($expireAt)->gt(Carbon::today())) {
                    $dietUser->expire_at = Carbon::parse($expireAt)->addDays($days);
                } else {
                    // اگر تاریخ نداشت یا منقضی شده بود
                    $dietUser->expire_at = Carbon::now()->addDays($days);
                }


                $dietUser->save();
            }
            elseif ($data['plan_id']<0) {
                $days = -1 * $data['plan_id'];

                $expireAt = $dietUser->expire_at;

                // اگر قبلاً تاریخ داشت و هنوز منقضی نشده
                if ($expireAt && Carbon::parse($expireAt)->gt(Carbon::today())) {
                    $dietUser->expire_at = Carbon::parse($expireAt)->removeDays($days);
                } 


                $dietUser->save();
            }
        }
        return response()->json($calorie, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/subscriptions/{id}",
     *     summary="ویرایش اشتراک",
     *     description="این متد یک اشتراک موجود را ویرایش می‌کند.",
     *     tags={"Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه اشتراک",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","plan_id"},
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="plan_id", type="integer"),
     *             @OA\Property(property="price", type="number", format="float", nullable=true),
     *             @OA\Property(property="payment_id", type="string", maxLength=100, nullable=true),
     *             @OA\Property(property="status", type="string", enum={"active","expired","cancelled","pending"}, nullable=true),
     *             @OA\Property(property="start_date", type="string", format="date", nullable=true),
     *         )
     *     ),
     *     @OA\Response(response=200, description="اشتراک بروزرسانی شد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="رکورد یافت نشد")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $calorie = Subscription::find($id);
        if (!$calorie) {
            return response()->json(['message' => 'رکورد یافت نشد'], 404);
        }
        $data = $request->validate([
            'user_id'    => 'required|exists:diet_users,id',
            'plan_id'    => 'nullable|numeric',
            'price'      => 'nullable|numeric',
            'payment_id' => 'nullable|string|max:100',
            'status'     => 'nullable|in:active,expired,cancelled,pending',
            'start_date' => 'nullable|date',
        ]);

        $calorie->update($data);
        return response()->json($calorie);
    }

    /**
     * @OA\Delete(
     *     path="/api/subscriptions/{id}",
     *     summary="حذف اشتراک",
     *     description="این متد یک اشتراک را حذف می‌کند.",
     *     tags={"Subscriptions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="شناسه اشتراک",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="رکورد حذف شد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="رکورد یافت نشد")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !$user->is_superuser) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $subscription = Subscription::find($id);
        if (!$subscription) {
        return response()->json(['message' => 'رکورد یافت نشد'], 404);
        }

        $dietUser = User::find($subscription->user_id);
        if ($dietUser && $subscription->plan_id && $subscription->status == 'active') {
            $days = $subscription->plan_id;
            if ($dietUser->expire_at) {
                $dietUser->expire_at = Carbon::parse($dietUser->expire_at)->subDays($days);
            } else {
                $dietUser->expire_at = null;
            }
            $dietUser->save();
        }

        $subscription->delete();
        return response()->json(['message' => 'رکورد حذف شد']);
    }


    public function handleBankCallback()
    {

    }
}
