<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietItem;
use App\Models\DietMeal;
use App\Models\DietMealItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Enums\MealType;
use App\Enums\FoodCulture;
use App\Enums\MealCategory;
use App\Models\Image;
use App\Models\DietMealType;
use App\Models\DietMealCategory;
use App\Models\DietMealCulture;
use Illuminate\Support\Str;
use App\Services\OpenAIService;
use App\Models\DietWeeklyCulture;
use Carbon\Carbon;

class DietMealController extends Controller
{
    private const STAFF_ROLES = ['super_admin', 'nutrition_expert', 'support'];

    // حداکثر تعداد وعده‌های در انتظار تایید هر کاربر
    private const MAX_PENDING_USER_MEALS = 10;

    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * @OA\Get(
     *     path="/api/meal-types",
     *     summary="دریافت لیست انواع وعده‌های غذایی (عدد و نام)",
     *     tags={"MealType"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست انواع وعده‌های غذایی",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="label", type="string", example="صبحانه")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function mealTypes()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert','support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
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

        $allMeals = MealType::getList();

        usort($allMeals, function ($a, $b) use ($mealOrder) {
            $posA = array_search($a['label'], $mealOrder);
            $posB = array_search($b['label'], $mealOrder);

            // اگر label پیدا نشد، در انتهای لیست قرار گیرد
            $posA = $posA === false ? PHP_INT_MAX : $posA;
            $posB = $posB === false ? PHP_INT_MAX : $posB;

            return $posA <=> $posB;
        });

        return response()->json($allMeals);
    }

    /**
     * @OA\Get(
     *     path="/api/diet-meals",
     *     summary="دریافت لیست وعده‌های غذایی با امکان فیلتر بر اساس نوع، دسته‌بندی، فرهنگ غذایی و صفحه‌بندی",
     *     tags={"DietMeal"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="شماره صفحه برای صفحه‌بندی",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم‌ها در هر صفحه",
     *         @OA\Schema(type="integer", example=20)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="جستجو بر اساس نام وعده غذایی",
     *         @OA\Schema(type="string", example="ناهار سنتی")
     *     ),
     *     @OA\Parameter(
     *         name="meal_type_id",
     *         in="query",
     *         description="شناسه نوع وعده غذایی برای فیلتر",
     *         @OA\Schema(type="integer", example=2)
     *     ),
     *     @OA\Parameter(
     *         name="meal_category_id[]",
     *         in="query",
     *         description="آرایه‌ای از شناسه‌های دسته‌بندی وعده غذایی برای فیلتر (مثلاً ?meal_category_id[]=1&meal_category_id[]=2)",
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="integer", example=1)
     *         ),
     *         style="form",
     *         explode=true
     *     ),
     *     @OA\Parameter(
     *         name="food_culture_id[]",
     *         in="query",
     *         description="آرایه‌ای از شناسه‌های فرهنگ غذایی برای فیلتر (مثلاً ?food_culture_id[]=1&food_culture_id[]=2)",
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="integer", example=1)
     *         ),
     *         style="form",
     *         explode=true
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="عملیات موفق",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=5),
     *                 @OA\Property(property="name", type="string", example="صبحانه ایرانی"),
     *                 @OA\Property(property="description", type="string", example="یک وعده سالم برای صبحانه"),
     *                 @OA\Property(property="imageUrl", type="string", example="https://example.com/images/meal1.jpg"),
     *                 @OA\Property(property="mealTypes", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="صبحانه")
     *                 )),
     *                 @OA\Property(property="mealCategories", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="خورشت")
     *                 )),
     *                 @OA\Property(property="foodCultures", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="label", type="string", example="ایرانی")
     *                 )),
     *                 @OA\Property(property="items", type="array", @OA\Items(
     *                     @OA\Property(property="itemId", type="integer", example=12),
     *                     @OA\Property(property="title", type="string", example="تخم‌مرغ"),
     *                     @OA\Property(property="percent", type="integer", example=30)
     *                 ))
     *             )),
     *             @OA\Property(property="totalCount", type="integer", example=100)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="عدم دسترسی (کاربر احراز هویت نشده یا مجاز نیست)"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }

        $pageSize = (int)($request->pagesize ?? 20);
        $isStaff = $user->hasAnyRole(self::STAFF_ROLES);
        $query = DietMeal::with(['items', 'mealTypes', 'foodCultures', 'user']);

        if ($isStaff) {
            // پیش‌فرض فقط وعده‌های تایید شده؛ status=all همه وعده‌ها
            $status = $request->input('status', DietMeal::STATUS_APPROVED);
            if ($status !== 'all') {
                $query->where('status', $status);
            }
            if ($request->boolean('user_created')) {
                $query->whereNotNull('user_id');
            }
        } else {
            // کاربر عادی: وعده‌های تایید شده + وعده‌های در انتظار تایید خودش
            $query->where(function ($q) use ($user) {
                $q->where('status', DietMeal::STATUS_APPROVED)
                    ->orWhere(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->where('status', DietMeal::STATUS_PENDING);
                    });
            });
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        if ($request->filled('meal_type_id')) {
            $query->whereHas('mealTypes', function ($q) use ($request) {
                $q->where('meal_type_id', $request->meal_type_id);
            });
        }

        if ($request->filled('meal_category_id')) {
            $categoryIds = is_array($request->meal_category_id)
                ? $request->meal_category_id
                : explode(',', $request->meal_category_id);

            $query->whereHas('mealCategories', function ($q) use ($categoryIds) {
                $q->whereIn('meal_category_id', $categoryIds);
            });
        }

        if ($request->filled('food_culture_id')) {
            $cultureIds = is_array($request->food_culture_id) ? $request->food_culture_id : [$request->food_culture_id];
            $query->whereHas('foodCultures', function ($q) use ($cultureIds) {
                $q->whereIn('food_culture_id', $cultureIds);
            });
        } elseif (!$isStaff && $user->usesFoodCulture()) {
            // برنامه عادی: فقط وعده‌های فرهنگ غذایی کاربر؛ سایر برنامه‌ها بین‌المللی هستند
            $query->whereHas('foodCultures', function ($q) use ($user) {
                $q->where('food_culture_id', $user->food_culture);
            });
        }
        $totalCount = $query->count();
        $meals = $query->orderBy('id', 'desc')->paginate($pageSize);
        $meals = collect($meals->items())->map(fn($meal) => $this->mealToArray($meal, $user, $isStaff));

        return response()->json([
            'result' => $meals,
            'totalCount' => $totalCount,
        ]);
    }

    private function mealToArray(DietMeal $meal, $user, bool $isStaff = false): array
    {
        $output = [
            'id' => $meal->id,
            'name' => $meal->name,
            'description' => $meal->description,
            'imageUrl' => $meal->image?->url(),
            'status' => $meal->status,
            'isMine' => $meal->user_id !== null && $meal->user_id === $user->id,
            'mealTypes' => $meal->mealTypes->map(function ($t) {
                return [
                    'id' => $t->meal_type_id,
                    'label' => $t->meal_type_id ? MealType::from($t->meal_type_id)->label() : null,

                ];
            }),
            'mealCategories' => $meal->mealCategories->map(function ($t) {
                return [
                    'id' => $t->meal_category_id,
                    'label' => $t->meal_category_id ? MealCategory::from($t->meal_category_id)->label() : null,

                ];
            }),
            'foodCultures' => $meal->foodCultures->map(function ($f) {
                return [
                    'id' => $f->food_culture_id,
                    'label' => $f->food_culture_id ? FoodCulture::from($f->food_culture_id)->label() : null,
                ];
            }),

            'items' => $meal->items->map(fn($i) => [
                'itemId' => $i->itemId,
                'title' => $i->item->name ?? null,
                'percent' => $i->percent
            ])
        ];

        if ($isStaff) {
            $output['creator'] = $meal->user ? [
                'id' => $meal->user->id,
                'name' => trim($meal->user->first_name . ' ' . $meal->user->last_name),
                'phone' => $meal->user->phone,
            ] : null;
            $output['created_at'] = $meal->created_at;
        }

        return $output;
    }

    private function aiTextToHtml($text)
    {
        // بولدها
        $text = preg_replace('/\*\*(.*?)\*\*/', '<h4>$1</h4>', $text);

        // لیست‌ها
        $lines = explode("\n", $text);

        $html = '';
        $inList = false;

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            if (str_starts_with($line, '- ')) {

                if (!$inList) {
                    $html .= '<ul>';
                    $inList = true;
                }

                $html .= '<li>' . substr($line, 2) . '</li>';
            } else {

                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }

                if (str_contains($line, '<h4>')) {
                    $html .= $line;
                } else {
                    $html .= '<p>' . $line . '</p>';
                }
            }
        }

        if ($inList) {
            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * @OA\Get(
     *     path="/api/diet-meals/{id}",
     *     summary="جزئیات وعده غذایی",
     *     tags={"DietUserWeekly"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="موفق")
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $meal = DietMeal::with(['items', 'mealTypes', 'foodCultures'])->find($id);
        if (!$meal) {
            return response()->json(['message' => 'یافت نشد.'], 404);
        }
        // وعده تایید نشده فقط برای سازنده و کارشناسان قابل مشاهده است
        if (
            $meal->status !== DietMeal::STATUS_APPROVED
            && $meal->user_id !== $user->id
            && !$user->hasAnyRole(self::STAFF_ROLES)
        ) {
            return response()->json(['message' => 'یافت نشد.'], 404);
        }
        //$imageUrlAi = null;
        /*$itemNames = $meal->items
            ->pluck('item.name')
            ->filter()
            ->take(3)
            ->values()
            ->toArray();*/


        if(empty($meal->description)) {
            $mealNameAr = $meal->name;
            $Prompt = "
            اسم الوجبة : {$mealNameAr}

            المطلوب: تقديم تفاصيل الوجبة لغرض دايت :

            المكونات الرئيسية: اكتب العنوان في سطر، والمكونات في السطر الذي يليه مباشرة (ذكر المكونات فقط دون كميات أو مقادير).
            طريقة التحضير: اكتب العنوان في سطر، والخطوات في سطر واحد مختصر أو نقاط قصيرة جداً.
            الفوائد الصحية: اكتب العنوان في سطر، والفوائد في نقاط مختصرة تركز على (الهدف المختار أعلاه).
            ملاحظة هامة: إذا كانت الوجبة تتكون من عنصر واحد فقط (مثل: التمر أو التفاح)، اكتفِ بذكر \"الفوائد الصحية\" فقط وتجاهل الأقسام الأخرى.
            التنسيق: اجعل العناوين بارزة واللغة واضحة وبسيطة في اقل من 700 کاراکتر
            ";

            //dd(env('OPENAI_API_KEY'));
            $openAI = app(OpenAIService::class);

            $aiDescription = $openAI->chat([
            [
                'role' => 'system',
                'content' => 'You are a professional arabic dietitian and health analyst.'
            ],
            [
                'role' => 'user',
                'content' => $Prompt
            ]
            ]);

            if ($aiDescription) {
                $meal->description = $this->aiTextToHtml($aiDescription);
                $meal->save();
            }
        }

        $output = [
            'id' => $meal->id,
            'name' => $meal->name,
            'description' => $meal->description,
            'imageUrl' => $meal->image?->url(),
            'status' => $meal->status,
            'isMine' => $meal->user_id !== null && $meal->user_id === $user->id,
            //'imageUrlAi' => $imageUrlAi,
            'mealTypes' => $meal->mealTypes->map(function ($t) {
                return [
                    'id' => $t->meal_type_id,
                    'label' => $t->meal_type_id ? MealType::from($t->meal_type_id)->label() : null,

                ];
            }),
            'mealCategories' => $meal->mealCategories->map(function ($t) {
                return [
                    'id' => $t->meal_category_id,
                    'label' => $t->meal_category_id ? MealCategory::from($t->meal_category_id)->label() : null,

                ];
            }),
            'foodCultures' => $meal->foodCultures->map(function ($f) {
                return [
                    'id' => $f->food_culture_id,
                    'label' => $f->food_culture_id ? FoodCulture::from($f->food_culture_id)->label() : null,
                ];
            }),

            'items' => $meal->items->map(fn($i) => [
                'itemId' => $i->itemId,
                'title' => $i->item->name ?? null,
                'weightUnit' => $i->item->weightUnit ?? null,
                'percent' => $i->percent
            ])
        ];


        return response()->json($output);
    }

    /**
     * @OA\Get(
     *     path="/api/diet-meals/{mealId}/items",
     *     summary="دریافت لیست آیتم‌های یک وعده غذایی",
     *     tags={"DietMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="mealId",
     *         in="path",
     *         description="شناسه وعده غذایی",
     *         required=true,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="لیست آیتم‌ها",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="itemId", type="integer"),
     *             @OA\Property(property="itemName", type="string"),
     *             @OA\Property(property="percent", type="integer"),
     *             @OA\Property(property="calories", type="integer"),
     *             @OA\Property(property="unit", type="string")
     *         ))
     *     ),
     *     @OA\Response(response=404, description="وعده پیدا نشد"),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function itemsByMeal($mealId)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert','support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meal = DietMeal::find($mealId);
        if (!$meal) {
            return response()->json(['message' => 'وعده غذایی پیدا نشد.'], 404);
        }

        $items = DietMealItem::where('mealId', $mealId)
            ->with('item') // رابطه با DietItem
            ->get()
            ->map(function ($row) {
                return [
                    'itemId' => $row->itemId,
                    'itemName' => optional($row->item)->name,
                    'percent' => $row->percent,
                    'calories' => optional($row->item)->calories,
                    'unit' => optional($row->item)->unit,
                ];
            });

        return response()->json($items);
    }

    /**
     * @OA\Post(
     *     path="/api/diet-meals/{id}",
     *     operationId="updateDietMeal",
     *     summary="ویرایش وعده غذایی",
     *     tags={"DietMeal"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "mealTypes", "foodCultures"},
     *                 @OA\Property(property="name", type="string", example="ناهار"),
     *                 @OA\Property(property="description", type="string", example="وعده اصلی روز"),
     *                 @OA\Property(property="image", type="file", description="تصویر وعده"),
     *                 @OA\Property(property="mealTypes", type="array", @OA\Items(type="integer", example=1)),
     *                 @OA\Property(property="foodCultures", type="array", @OA\Items(type="integer", example=2)),
     *                 @OA\Property(property="mealCategories", type="array", @OA\Items(type="integer", example=3)),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="itemId", type="integer", example=5),
     *                         @OA\Property(property="percent", type="integer", example=50)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="وعده غذایی با موفقیت ویرایش شد")
     * )
     */

    public function update(Request $request, $id)
    {

        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert','support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $meal = DietMeal::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'mealTypes' => 'array',
            'mealTypes.*' => 'nullable|integer',

            'foodCultures' => 'array',
            'foodCultures.*' => 'nullable|integer',

        ]);

        $imageId = $meal->imageId; // نگه‌داری عکس قبلی

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $token = Str::random(32);
            $extension = $file->getClientOriginalExtension();
            $filename = $token . '.' . $extension;

            // مسیر آپلود در public
            $uploadPath = public_path('uploads/images/' . date('Y/m'));

            // اگه پوشه وجود نداشت بساز
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0775, true);
            }

            // انتقال فایل
            $file->move($uploadPath, $filename);

            // مسیر نهایی (نسبت به public)
            $url = 'uploads/images/' . date('Y/m') . '/' . $filename;

            // ذخیره رکورد در جدول images
            $image = Image::create([
                'user_id'   => $user->id,
                'name'      => $request->name,
                'token'     => $token,
                'extension' => $extension,
                'url'       => $filename,
                'dimension' => json_encode(getimagesize(public_path($url))),
                'month'     => date('m'),
                'year'      => date('Y'),
            ]);

            $imageId = $image->id;

        }
        if($request->delImage == 1)
        {
            $imageId = null;
        }
        // آپدیت وعده غذایی
        $meal->update([
            'name'        => $request->name,
            'description' => $request->description,
            'imageId'    => $imageId,
        ]);


        if ($request->filled('mealTypes')) {
            // آپدیت mealTypes
            DietMealType::where('meal_id', $meal->id)->delete();
            foreach ($request->mealTypes as $typeId) {
                DietMealType::create([
                    'meal_id' => $meal->id,
                    'meal_type_id' => $typeId
                ]);
            }
        }
        if ($request->filled('mealCategories')) {
            DietMealCategory::where('meal_id', $meal->id)->delete();
            foreach ($request->mealCategories ?? [] as $categoryId) {
                DietMealCategory::create([
                    'meal_id' => $meal->id,
                    'meal_category_id' => $categoryId
                ]);
            }
        }

        if ($request->filled('foodCultures')) {
            // آپدیت foodCultures
            DietMealCulture::where('meal_id', $meal->id)->delete();
            foreach ($request->foodCultures as $cultureId) {
                DietMealCulture::create([
                    'meal_id' => $meal->id,
                    'food_culture_id' => $cultureId
                ]);
            }
        }

        if ($request->filled('items')) {
            // آپدیت items
            DietMealItem::where('mealId', $meal->id)->delete();
            foreach ($request->items as $item) {
                DietMealItem::create([
                    'mealId' => $meal->id,
                    'itemId' => $item['itemId'],
                    'percent' => $item['percent'] ?? 100,
                ]);
            }
        }

        return response()->json(['message' => 'وعده با موفقیت ویرایش شد.']);
    }


    /**
     * @OA\Post(
     *     path="/api/diet-meals",
     *     summary="ایجاد وعده غذایی جدید",
     *     tags={"DietMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "mealTypes", "foodCultures"},
     *                 @OA\Property(property="name", type="string", example="ناهار"),
     *                 @OA\Property(property="description", type="string", example="وعده اصلی روز"),
     *                 @OA\Property(property="image", type="file", description="تصویر وعده"),
     *                 @OA\Property(property="mealTypes", type="array", @OA\Items(type="integer", example=1)),
     *                 @OA\Property(property="foodCultures", type="array", @OA\Items(type="integer", example=2)),
     *                 @OA\Property(property="mealCategories", type="array", @OA\Items(type="integer", example=3)),
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="itemId", type="integer", example=5),
     *                         @OA\Property(property="percent", type="integer", example=50)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="وعده با موفقیت ایجاد شد.")
     * )
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert','support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp',
            'mealTypes' => 'array',
            'mealTypes.*' => 'required|integer',
            'mealCategories' => 'array',
            'mealCategories.*' => 'nullable|integer',
            'foodCultures' => 'array',
            'foodCultures.*' => 'required|integer',
            'items' => 'array',
            'items.*.itemId' => 'required|integer',
            'items.*.percent' => 'nullable|integer'
        ]);

        $imageId = null;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $token = Str::random(32);
            $extension = $file->getClientOriginalExtension();
            $filename = $token . '.' . $extension;

            // مسیر آپلود در public
            $uploadPath = public_path('uploads/images/' . date('Y/m'));

            // اگه پوشه وجود نداشت بساز
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0775, true);
            }

            // انتقال فایل
            $file->move($uploadPath, $filename);

            // مسیر نهایی (نسبت به public)
            $url = 'uploads/images/' . date('Y/m') . '/' . $filename;

            // ذخیره رکورد در جدول images
            $image = Image::create([
                'user_id'   => $user->id,
                'name'      => $request->name,
                'token'     => $token,
                'extension' => $extension,
                'url'       => $filename,
                'dimension' => json_encode(getimagesize(public_path($url))),
                'month'     => date('m'),
                'year'      => date('Y'),
            ]);

            $imageId = $image->id;
        }

        $meal = DietMeal::create([
            'name' => $request->name,
            'description' => $request->description,
            'imageId' => $imageId,
        ]);

        foreach ($request->mealTypes ?? [] as $typeId) {
            DietMealType::create([
                'meal_id' => $meal->id,
                'meal_type_id' => $typeId
            ]);
        }

        foreach ($request->mealCategories ?? [] as $categoryId) {
            DietMealCategory::create([
                'meal_id' => $meal->id,
                'meal_category_id' => $categoryId
            ]);
        }

        foreach ($request->foodCultures ?? [] as $cultureId) {
            DietMealCulture::create([
                'meal_id' => $meal->id,
                'food_culture_id' => $cultureId
            ]);
        }

        foreach ($request->items ?? [] as $item) {
            DietMealItem::create([
                'mealId' => $meal->id,
                'itemId' => $item['itemId'],
                'percent' => $item['percent'] ?? 100,
            ]);
        }

        return response()->json(['message' => 'وعده با موفقیت ایجاد شد.'], 201);
    }


    /**
     * @OA\Delete(
     *     path="/api/diet-meals/{id}",
     *     summary="حذف وعده غذایی",
     *     tags={"DietMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="وعده با موفقیت حذف شد."),
     *     @OA\Response(response=401, description="دسترسی غیرمجاز")
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'nutrition_expert','support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $meal = DietMeal::findOrFail($id);
        $meal->mealTypes()->delete();
        $meal->foodCultures()->delete();
        $meal->items()->delete();
        $meal->delete();

        return response()->json(['message' => 'وعده با موفقیت حذف شد.']);
    }

    /**
     * @OA\Get(
     *     path="/api/user-meals/items",
     *     summary="جستجوی آیتم‌های غذایی برای ساخت وعده توسط کاربر (آیتم‌های فرهنگ غذایی کاربر اول نمایش داده می‌شوند)",
     *     tags={"UserMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="name", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="pagesize", in="query", @OA\Schema(type="integer", example=20)),
     *     @OA\Response(response=200, description="لیست آیتم‌ها همراه با totalCount")
     * )
     */
    public function userItems(Request $request)
    {
        $user = Auth::user();

        $pageSize = min(max((int)($request->pagesize ?? 20), 1), 50);
        $query = DietItem::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        $totalCount = $query->count();

        // برنامه عادی: آیتم‌های فرهنگ غذایی کاربر اول؛ سایر برنامه‌ها بین‌المللی هستند
        if ($user->usesFoodCulture()) {
            $query->orderByRaw('foodCultureId = ? desc', [$user->food_culture]);
        }
        $items = $query->orderBy('name')->paginate($pageSize);

        $items = collect($items->items())->map(fn($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'unit' => $item->unit,
            'caloriesGram' => $item->caloriesGram,
            'weightUnit' => $item->weightUnit,
            'foodCultureId' => $item->foodCultureId,
        ]);

        return response()->json([
            'result' => $items,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/user-meals",
     *     summary="لیست وعده‌های ساخته‌شده توسط کاربر جاری همراه با وضعیت تایید",
     *     tags={"UserMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="لیست وعده‌ها همراه با totalCount")
     * )
     */
    public function userMeals(Request $request)
    {
        $user = Auth::user();

        $pageSize = (int)($request->pagesize ?? 20);
        $query = DietMeal::with(['items', 'mealTypes', 'foodCultures'])
            ->where('user_id', $user->id);

        $totalCount = $query->count();
        $meals = $query->orderBy('id', 'desc')->paginate($pageSize);

        return response()->json([
            'result' => collect($meals->items())->map(fn($meal) => $this->mealToArray($meal, $user)),
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/user-meals",
     *     summary="ساخت وعده توسط کاربر؛ وعده با فرهنگ غذایی کاربر و وضعیت در انتظار تایید ذخیره می‌شود",
     *     tags={"UserMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "mealTypes", "items"},
     *             @OA\Property(property="name", type="string", example="فول مدمس"),
     *             @OA\Property(property="mealTypes", type="array", @OA\Items(type="integer", example=1)),
     *             @OA\Property(
     *                 property="items",
     *                 type="array",
     *                 description="مجموع درصدها باید ۱۰۰ باشد",
     *                 @OA\Items(
     *                     @OA\Property(property="itemId", type="integer", example=5),
     *                     @OA\Property(property="percent", type="integer", example=50)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="وعده ایجاد شد و در انتظار تایید است"),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی")
     * )
     */
    public function userStore(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'mealTypes' => 'required|array|min:1',
            'mealTypes.*' => ['required', 'integer', Rule::in(array_column(MealType::cases(), 'value'))],
            'items' => 'required|array|min:1|max:20',
            'items.*.itemId' => 'required|integer|distinct|exists:diet_item,id',
            'items.*.percent' => 'required|integer|min:1|max:100',
        ]);

        if (!$user->food_culture) {
            throw ValidationException::withMessages([
                'food_culture' => 'يرجى تحديد ثقافتك الغذائية في الملف الشخصي أولاً.',
            ]);
        }

        if (array_sum(array_column($request->items, 'percent')) !== 100) {
            throw ValidationException::withMessages([
                'items' => 'يجب أن يكون مجموع النسب 100٪.',
            ]);
        }

        $pendingCount = DietMeal::where('user_id', $user->id)
            ->where('status', DietMeal::STATUS_PENDING)
            ->count();
        if ($pendingCount >= self::MAX_PENDING_USER_MEALS) {
            throw ValidationException::withMessages([
                'name' => 'لديك عدد كبير من الوجبات بانتظار المراجعة، يرجى الانتظار حتى تتم مراجعتها.',
            ]);
        }

        $meal = DB::transaction(function () use ($request, $user) {
            $meal = DietMeal::create([
                'name' => $request->name,
                'user_id' => $user->id,
                'status' => DietMeal::STATUS_PENDING,
            ]);

            foreach (array_unique($request->mealTypes) as $typeId) {
                DietMealType::create([
                    'meal_id' => $meal->id,
                    'meal_type_id' => $typeId,
                ]);
            }

            DietMealCulture::create([
                'meal_id' => $meal->id,
                'food_culture_id' => $user->food_culture,
            ]);

            foreach ($request->items as $item) {
                DietMealItem::create([
                    'mealId' => $meal->id,
                    'itemId' => $item['itemId'],
                    'percent' => $item['percent'],
                ]);
            }

            return $meal;
        });

        return response()->json([
            'message' => 'تم إنشاء الوجبة وستظهر للجميع بعد موافقة الخبير.',
            'id' => $meal->id,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/diet-meals/{id}/status",
     *     summary="تایید یا رد وعده ساخته‌شده توسط کاربر",
     *     tags={"DietMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"pending", "approved", "rejected"})
     *         )
     *     ),
     *     @OA\Response(response=200, description="وضعیت وعده تغییر کرد")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(self::STAFF_ROLES)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => ['required', Rule::in(DietMeal::STATUSES)],
        ]);

        $meal = DietMeal::findOrFail($id);
        $meal->update(['status' => $request->status]);

        return response()->json(['message' => 'وضعیت وعده تغییر کرد.']);
    }

    /**
     * @OA\Get(
     *     path="/api/meal-categories",
     *     summary="دریافت لیست مجموعه‌های وعده غذایی",
     *     tags={"DietMeal"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="لیست مجموعه‌های وعده غذایی",
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
    public function MealCategories()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز'], 401);
        }

        return response()->json(MealCategory::getList());
    }
}
