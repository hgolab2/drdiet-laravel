<?php

namespace App\Http\Controllers\Api;

use App\Enums\DietType;
use App\Enums\WeightGoal;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Calorie;
use Illuminate\Http\Request;

class CalorieController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            if (!$user || !$user->hasAnyRole(['super_admin', 'nutrition_expert'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            return $next($request);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/calories",
     *     summary="دریافت لیست کالری‌ها",
     *     description="این متد لیستی از کالری‌ها را بر اساس فیلتر dietTypeId و با قابلیت صفحه‌بندی بازمی‌گرداند.",
     *     tags={"Calories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="dietTypeId",
     *         in="query",
     *         description="شناسه نوع رژیم",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم‌ها در هر صفحه",
     *         required=false,
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="لیست کالری‌ها با موفقیت بازگردانده شد"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $user = Auth::user();


        $pageSize = (int)($request->pagesize ?? 20);
        $query = Calorie::query();

        if ($request->filled('dietTypeId')) {
            $query->where('dietTypeId',  $request->dietTypeId);
        }

        $totalCount = $query->count();
        $items = $query->orderBy('id', 'desc')->paginate($pageSize);
        $items = array_map(function ($item) {
            return [
                'id' => $item->id,
                'dietTypeId' => $item->dietTypeId,
                'dietType' => $item->dietTypeId ? DietType::from($item->dietTypeId)->label() : null,
                'breakfast' => $item->breakfast,
                'breakfastType' => $item->breakfastType,
                'lunch' => $item->lunch,
                'lunchType' => $item->lunchType,
                'dinner' => $item->dinner,
                'dinnerType' => $item->dinnerType,
                'morningSnack' => $item->morningSnack,
                'morningSnackType' => $item->morningSnackType,
                'preLunch' => $item->preLunch,
                'preLunchType' => $item->preLunchType,
                'fatPortion' => $item->fatPortion,
                'fatPortionType' => $item->fatPortionType,
                'sugarPortion' => $item->sugarPortion,
                'sugarPortionType' => $item->sugarPortionType,
                'dairyPortion' => $item->dairyPortion,
                'dairyPortionType' => $item->dairyPortionType,
                'afternoonSnack2' => $item->afternoonSnack2,
                'afternoonSnack2Type' => $item->afternoonSnack2Type,
                'afterDinner' => $item->afterDinner,
                'afterDinnerType' => $item->afterDinnerType,
                'compulsoryShare' => $item->compulsoryShare,
                'compulsoryShareType' => $item->compulsoryShareType,
                'breastfeedingShare' => $item->breastfeedingShare,
                'breastfeedingShareType' => $item->breastfeedingShareType,
            ];
        }, $items->items());

        return response()->json([
            'result' => $items,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/calories",
     *     summary="ایجاد رکورد کالری جدید",
     *     description="این متد یک رکورد جدید از اطلاعات کالری را ثبت می‌کند.",
     *     tags={"Calories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="dietTypeId", type="integer", example=1),
     *             @OA\Property(property="breakfast", type="integer", example=400),
     *             @OA\Property(property="breakfastType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="morningSnack", type="integer", example=100),
     *             @OA\Property(property="morningSnackType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="preLunch", type="integer", example=150),
     *             @OA\Property(property="preLunchType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="lunch", type="integer", example=600),
     *             @OA\Property(property="lunchType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="afternoonSnack2", type="integer", example=120),
     *             @OA\Property(property="afternoonSnack2Type", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="dinner", type="integer", example=500),
     *             @OA\Property(property="dinnerType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="afterDinner", type="integer", example=80),
     *             @OA\Property(property="afterDinnerType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="sugarPortion", type="integer", example=2),
     *             @OA\Property(property="sugarPortionType", type="string", enum={"percent","amount"}, example="amount"),
     *             @OA\Property(property="fatPortion", type="integer", example=3),
     *             @OA\Property(property="fatPortionType", type="string", enum={"percent","amount"}, example="amount"),
     *             @OA\Property(property="dairyPortion", type="integer", example=2),
     *             @OA\Property(property="dairyPortionType", type="string", enum={"percent","amount"}, example="amount"),
     *             @OA\Property(property="compulsoryShare", type="integer", example=150),
     *             @OA\Property(property="compulsoryShareType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="breastfeedingShare", type="integer", example=200),
     *             @OA\Property(property="breastfeedingShareType", type="string", enum={"percent","amount"}, example="percent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="رکورد با موفقیت ایجاد شد"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     )
     * )
     */

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'dietTypeId' => 'nullable|integer',
            'dinner' => 'nullable|integer',
            'dinnerType' => 'required|in:percent,amount',
            'afternoonSnack2' => 'nullable|integer',
            'afternoonSnack2Type' => 'required|in:percent,amount',
            'lunch' => 'nullable|integer',
            'lunchType' => 'required|in:percent,amount',
            'preLunch' => 'nullable|integer',
            'preLunchType' => 'required|in:percent,amount',
            'morningSnack' => 'nullable|integer',
            'morningSnackType' => 'required|in:percent,amount',
            'breakfast' => 'nullable|integer',
            'breakfastType' => 'required|in:percent,amount',
            'sugarPortion' => 'nullable|integer',
            'sugarPortionType' => 'required|in:percent,amount',
            'fatPortion' => 'nullable|integer',
            'fatPortionType' => 'required|in:percent,amount',
            'dairyPortion' => 'nullable|integer',
            'dairyPortionType' => 'required|in:percent,amount',
            'afterDinner' => 'nullable|integer',
            'afterDinnerType' => 'required|in:percent,amount',
            'compulsoryShare' => 'nullable|integer',
            'compulsoryShareType' => 'required|in:percent,amount',
            'breastfeedingShare' => 'nullable|integer',
            'breastfeedingShareType' => 'required|in:percent,amount',
        ]);

        $calorie = Calorie::create($data);

        return response()->json($calorie, 201);
    }


    /**
     * @OA\Put(
     *     path="/api/calories/{id}",
     *     summary="ویرایش یک رکورد کالری",
     *     description="با استفاده از این متد می‌توان رکورد کالری موجود را ویرایش کرد.",
     *     tags={"Calories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه رکورد کالری",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="dietTypeId", type="integer", example=1),
     *             @OA\Property(property="breakfast", type="integer", example=400),
     *             @OA\Property(property="breakfastType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="morningSnack", type="integer", example=100),
     *             @OA\Property(property="morningSnackType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="preLunch", type="integer", example=150),
     *             @OA\Property(property="preLunchType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="lunch", type="integer", example=600),
     *             @OA\Property(property="lunchType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="afternoonSnack2", type="integer", example=120),
     *             @OA\Property(property="afternoonSnack2Type", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="dinner", type="integer", example=500),
     *             @OA\Property(property="dinnerType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="afterDinner", type="integer", example=80),
     *             @OA\Property(property="afterDinnerType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="sugarPortion", type="integer", example=2),
     *             @OA\Property(property="sugarPortionType", type="string", enum={"percent","amount"}, example="amount"),
     *             @OA\Property(property="fatPortion", type="integer", example=3),
     *             @OA\Property(property="fatPortionType", type="string", enum={"percent","amount"}, example="amount"),
     *             @OA\Property(property="dairyPortion", type="integer", example=2),
     *             @OA\Property(property="dairyPortionType", type="string", enum={"percent","amount"}, example="amount"),
     *             @OA\Property(property="compulsoryShare", type="integer", example=150),
     *             @OA\Property(property="compulsoryShareType", type="string", enum={"percent","amount"}, example="percent"),
     *             @OA\Property(property="breastfeedingShare", type="integer", example=200),
     *             @OA\Property(property="breastfeedingShareType", type="string", enum={"percent","amount"}, example="percent")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="رکورد با موفقیت به‌روزرسانی شد"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="رکورد یافت نشد"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     )
     * )
     */

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $calorie = Calorie::find($id);
        if (!$calorie) {
            return response()->json(['message' => 'رکورد یافت نشد'], 404);
        }

        $data = $request->validate([
            'dietTypeId' => 'nullable|integer',
            'dinner' => 'nullable|integer',
            'dinnerType' => 'nullable|in:percent,amount',
            'afternoonSnack2' => 'nullable|integer',
            'afternoonSnack2Type' => 'nullable|in:percent,amount',
            'lunch' => 'nullable|integer',
            'lunchType' => 'nullable|in:percent,amount',
            'preLunch' => 'nullable|integer',
            'preLunchType' => 'nullable|in:percent,amount',
            'morningSnack' => 'nullable|integer',
            'morningSnackType' => 'nullable|in:percent,amount',
            'breakfast' => 'nullable|integer',
            'breakfastType' => 'nullable|in:percent,amount',
            'sugarPortion' => 'nullable|integer',
            'sugarPortionType' => 'nullable|in:percent,amount',
            'fatPortion' => 'nullable|integer',
            'fatPortionType' => 'nullable|in:percent,amount',
            'dairyPortion' => 'nullable|integer',
            'dairyPortionType' => 'nullable|in:percent,amount',
            'afterDinner' => 'nullable|integer',
            'afterDinnerType' => 'required|in:percent,amount',
            'compulsoryShare' => 'nullable|integer',
            'compulsoryShareType' => 'required|in:percent,amount',
            'breastfeedingShare' => 'nullable|integer',
            'breastfeedingShareType' => 'required|in:percent,amount',
        ]);

        $calorie->update($data);

        return response()->json($calorie);
    }

    /**
     * @OA\Delete(
     *     path="/api/calories/{id}",
     *     summary="حذف رکورد کالری",
     *     description="با استفاده از این متد می‌توان رکورد کالری را حذف کرد.",
     *     tags={"Calories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="شناسه رکورد کالری",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="رکورد با موفقیت حذف شد"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="رکورد یافت نشد"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز"
     *     )
     * )
     */

    public function destroy($id)
    {
        $user = Auth::user();

        $calorie = Calorie::find($id);
        if (!$calorie) {
            return response()->json(['message' => 'رکورد یافت نشد'], 404);
        }

        $calorie->delete();

        return response()->json(['message' => 'رکورد حذف شد']);
    }
}
