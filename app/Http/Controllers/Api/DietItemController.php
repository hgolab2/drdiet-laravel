<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\FoodCulture;

class DietItemController extends Controller
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
     *     path="/api/diet-items/list",
     *     summary="دریافت لیست آیتم‌های رژیمی (با فیلتر و صفحه‌بندی)",
     *     tags={"DietItem"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="شماره صفحه (پیش‌فرض 1)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم در هر صفحه (پیش‌فرض 20)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="جستجو بر اساس نام آیتم",
     *         required=false,
     *         @OA\Schema(type="string", example="برنج")
     *     ),
     *     @OA\Parameter(
     *         name="unit",
     *         in="query",
     *         description="جستجو بر اساس واحد اندازه‌گیری",
     *         required=false,
     *         @OA\Schema(type="string", example="گرم")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="لیست آیتم‌های رژیمی همراه با مجموع کل",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="سیب"),
     *                 @OA\Property(property="unit", type="string", example="عدد"),
     *                 @OA\Property(property="caloriesGram", type="number", format="float", example=52.3),
     *                 @OA\Property(property="weightUnit", type="integer", example=1),
     *                 @OA\Property(property="foodCulture", type="string", example="ایرانی"),
     *                 @OA\Property(property="foodCultureId", type="integer", example=2),
     *                 @OA\Property(property="atLeast", type="number", format="float", example=0.5),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-10-22T12:00:00Z")
     *             )),
     *             @OA\Property(property="totalCount", type="integer", example=134)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="دسترسی غیرمجاز (توکن اشتباه یا منقضی‌شده)"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $user = Auth::user();

        $pageSize = (int)($request->pagesize ?? 20);
        $query = DietItem::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }
        if ($request->filled('unit')) {
            $query->where('unit', 'like', "%{$request->unit}%");
        }

        $totalCount = $query->count();
        $items = $query->orderBy('id', 'desc')->paginate($pageSize);
        $items = array_map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'unit' => $item->unit,
                'caloriesGram' => $item->caloriesGram,
                'weightUnit' => $item->weightUnit,
                'foodCulture' => $item->foodCultureId ? FoodCulture::from($item->foodCultureId)->label() : null,
                'foodCultureId' => $item->foodCultureId,
                'atLeast' => $item->atLeast,
                'created_at' => $item->created_at,

            ];
        }, $items->items());

        return response()->json([
            'result' => $items,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/diet-items",
     *     summary="ایجاد آیتم جدید",
     *     tags={"DietItem"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name", "unit"},
     *             @OA\Property(property="name", type="string", example="برنج"),
     *             @OA\Property(property="unit", type="string", example="گرم"),
     *             @OA\Property(property="caloriesGram", type="number", format="float", example=120),
     *             @OA\Property(property="weightUnit", type="int", example=5),
     *             @OA\Property(property="foodCultureId", type="integer", example=1),
     *             @OA\Property(property="atLeast", type="number", format="float", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="آیتم با موفقیت ایجاد شد",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="برنج"),
     *             @OA\Property(property="unit", type="string", example="گرم"),
     *             @OA\Property(property="caloriesGram", type="number", format="float", example=120),
     *             @OA\Property(property="weightUnit", type="int", example=5),
     *             @OA\Property(property="foodCultureId", type="integer", example=1),
     *             @OA\Property(property="atLeast", type="number", format="float", example=50),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="updated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=400, description="درخواست نامعتبر")
     * )
     */

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:800',
            'unit' => 'required|string|max:200',
            'caloriesGram' => 'nullable|numeric',
            'weightUnit' => 'nullable|int',
            'foodCultureId' => 'nullable|integer',
            'atLeast' => 'nullable|numeric',
        ]);

        $item = DietItem::create($validated);
        return response()->json($item, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/diet-items/{id}",
     *     summary="نمایش یک آیتم",
     *     tags={"DietItem"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="آیتم یافت شد"),
     *     @OA\Response(response=404, description="یافت نشد")
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $item = DietItem::findOrFail($id);
        return response()->json($item);
    }

    /**
     * @OA\Put(
     *     path="/api/diet-items/{id}",
     *     summary="ویرایش آیتم",
     *     tags={"DietItem"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="unit", type="string"),
     *             @OA\Property(property="caloriesGram", type="float"),
     *             @OA\Property(property="weightUnit", type="int"),
     *             @OA\Property(property="foodCultureId", type="integer"),
     *             @OA\Property(property="atLeast", type="number", format="float")
     *         )
     *     ),
     *     @OA\Response(response=200, description="آیتم به‌روزرسانی شد"),
     *     @OA\Response(response=404, description="یافت نشد")
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();

        $item = DietItem::findOrFail($id);
        $item->update($request->only(['name', 'unit', 'caloriesGram', 'weightUnit', 'foodCultureId', 'atLeast']));
        return response()->json($item);
    }

    /**
     * @OA\Delete(
     *     path="/api/diet-items/{id}",
     *     summary="حذف آیتم",
     *     tags={"DietItem"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="حذف با موفقیت انجام شد"),
     *     @OA\Response(response=404, description="یافت نشد")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $item = DietItem::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
