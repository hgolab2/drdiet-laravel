<?php

namespace App\Http\Controllers\Api;

use App\Enums\DietType;
use App\Http\Controllers\Controller;
use App\Models\DietTip;
use App\Models\DietTipType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DietTipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/diet-tips",
     *     summary="لیست نکات کارشناس (پنل ادمین) به ترتیب اولویت",
     *     tags={"DietTip"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="diet_type_id", in="query", required=false, description="فقط نکات این نوع رژیم (بدون نکات عمومی)", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="is_active", in="query", required=false, @OA\Schema(type="integer", enum={0,1})),
     *     @OA\Parameter(name="pagesize", in="query", required=false, @OA\Schema(type="integer", default=20)),
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="text", type="string"),
     *                 @OA\Property(property="priority", type="integer", description="عدد کمتر = اولویت بالاتر"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="diet_types", type="array", description="خالی = نکته عمومی برای همه", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="label", type="string")
     *                 )),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="totalCount", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->canManage(Auth::user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = DietTip::query()->with('types');

        if ($request->filled('diet_type_id')) {
            $query->whereHas('types', fn ($q) => $q->where('diet_type_id', $request->diet_type_id));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->is_active);
        }

        $pageSize = (int) ($request->pagesize ?? 20);
        $totalCount = (clone $query)->count();
        $items = $query->orderBy('priority')->orderBy('id')->paginate($pageSize);

        return response()->json([
            'result' => array_map(fn ($tip) => $tip->toOutput(), $items->items()),
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/diet-tips",
     *     summary="ثبت نکته کارشناس",
     *     tags={"DietTip"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"text"},
     *             @OA\Property(property="text", type="string", maxLength=5000, example="روزانه حداقل ۸ لیوان آب بنوشید."),
     *             @OA\Property(property="priority", type="integer", minimum=1, nullable=true, description="عدد کمتر = اولویت بالاتر. خالی = انتهای لیست"),
     *             @OA\Property(property="is_active", type="boolean", nullable=true, example=true),
     *             @OA\Property(property="diet_type_ids", type="array", nullable=true, description="شناسه‌های نوع رژیم از /api/diet/types. خالی = برای همه", @OA\Items(type="integer"), example={1,4})
     *         )
     *     ),
     *     @OA\Response(response=201, description="ثبت شد"),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->canManage(Auth::user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'priority' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
            'diet_type_ids' => 'nullable|array',
            'diet_type_ids.*' => ['integer', Rule::enum(DietType::class)],
        ]);

        $tip = DB::transaction(function () use ($validated) {
            $tip = DietTip::create([
                'text' => $validated['text'],
                'priority' => $validated['priority'] ?? ((int) DietTip::max('priority')) + 1,
                'is_active' => $validated['is_active'] ?? true,
            ]);
            $this->syncTypes($tip, $validated['diet_type_ids'] ?? []);
            return $tip;
        });

        return response()->json($tip->load('types')->toOutput(), 201);
    }

    /**
     * @OA\Put(
     *     path="/api/diet-tips/{id}",
     *     summary="ویرایش نکته کارشناس",
     *     tags={"DietTip"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="text", type="string", maxLength=5000),
     *             @OA\Property(property="priority", type="integer", minimum=1),
     *             @OA\Property(property="is_active", type="boolean"),
     *             @OA\Property(property="diet_type_ids", type="array", description="اگر ارسال شود جایگزین لیست قبلی می‌شود. [] = برای همه", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=200, description="ویرایش شد"),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="یافت نشد"),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        if (!$this->canManage(Auth::user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tip = DietTip::find($id);
        if (!$tip) {
            return response()->json(['message' => 'نکته یافت نشد.'], 404);
        }

        $validated = $request->validate([
            'text' => 'sometimes|required|string|max:5000',
            'priority' => 'sometimes|required|integer|min:1',
            'is_active' => 'sometimes|required|boolean',
            'diet_type_ids' => 'sometimes|nullable|array',
            'diet_type_ids.*' => ['integer', Rule::enum(DietType::class)],
        ]);

        DB::transaction(function () use ($tip, $validated) {
            $tip->update(collect($validated)->only(['text', 'priority', 'is_active'])->all());
            if (array_key_exists('diet_type_ids', $validated)) {
                $this->syncTypes($tip, $validated['diet_type_ids'] ?? []);
            }
        });

        return response()->json($tip->load('types')->toOutput());
    }

    /**
     * @OA\Delete(
     *     path="/api/diet-tips/{id}",
     *     summary="حذف نکته کارشناس",
     *     tags={"DietTip"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="حذف شد"),
     *     @OA\Response(response=403, description="دسترسی غیرمجاز"),
     *     @OA\Response(response=404, description="یافت نشد")
     * )
     */
    public function destroy($id): JsonResponse
    {
        if (!$this->canManage(Auth::user())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tip = DietTip::find($id);
        if (!$tip) {
            return response()->json(['message' => 'نکته یافت نشد.'], 404);
        }

        DB::transaction(function () use ($tip) {
            $tip->types()->delete();
            $tip->delete();
        });

        return response()->json(['message' => 'حذف شد.']);
    }

    /**
     * @OA\Get(
     *     path="/api/diet-tips/user",
     *     summary="نکات کارشناس مربوط به کاربر (بر اساس نوع رژیم) به ترتیب اولویت",
     *     description="کاربر عادی نکات خودش را می‌گیرد؛ کارشناس‌ها می‌توانند user_id بفرستند.",
     *     tags={"DietTip"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user_id", in="query", required=false, description="فقط برای کارشناس‌ها", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="text", type="string"),
     *             @OA\Property(property="priority", type="integer")
     *         ))
     *     ),
     *     @OA\Response(response=403, description="دسترسی به کاربر دیگر مجاز نیست")
     * )
     */
    public function userTips(Request $request): JsonResponse
    {
        $authUser = Auth::user();

        $request->validate([
            'user_id' => 'nullable|integer|exists:diet_users,id',
        ]);

        $target = $authUser;
        if ($request->filled('user_id') && $request->user_id != $authUser->id) {
            if (!$authUser->hasAnyRole(['super_admin', 'nutrition_expert', 'support'])) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            $target = User::find($request->user_id);
        }

        return response()->json(DietTip::forDietType($target?->diet_type_id));
    }

    private function syncTypes(DietTip $tip, array $dietTypeIds): void
    {
        $tip->types()->delete();
        foreach (array_unique($dietTypeIds) as $dietTypeId) {
            DietTipType::create([
                'diet_tip_id' => $tip->id,
                'diet_type_id' => $dietTypeId,
            ]);
        }
    }

    private function canManage(?User $user): bool
    {
        return $user && $user->hasAnyRole(['super_admin', 'nutrition_expert']);
    }
}
