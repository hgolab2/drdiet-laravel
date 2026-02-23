<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\DietLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\WhatsappService;

class DietLeadController extends Controller
{
    protected WhatsappService $whatsappService;

    function getQuery($item){
        $query = str_replace(array('?'), array('\'%s\''), $item->toSql());
        return $query = vsprintf($query, $item->getBindings());
            //echo($query);
    }

    public function __construct(WhatsappService $whatsappService)
    {
        $this->middleware('auth:api')->except(['store' , 'edit']);;
        $this->whatsappService = $whatsappService;
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (preg_match('/^09\d{9}$/', $phone)) {
            $phone = '+98' . substr($phone, 1);
        }

        if (preg_match('/^9\d{9}$/', $phone)) {
            $phone = '+98' . $phone;
        }

        if (preg_match('/^98\d{10}$/', $phone)) {
            $phone = '+' . $phone;
        }

        return $phone;
    }



    /**
     * Assign expert to diet lead by WhatsApp number
     *
     * @OA\Post(
     *     path="/api/diet-leads/assign-expert",
     *     summary="Assign expert to a diet lead using WhatsApp number",
     *     tags={"DietLead"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_id","whatsapp_number"},
     *             @OA\Property(
     *                 property="customer_id",
     *                 type="integer",
     *                 example=15,
     *                 description="ID of the diet lead"
     *             ),
     *             @OA\Property(
     *                 property="whatsapp_number",
     *                 type="string",
     *                 example="+989123456789",
     *                 description="Expert WhatsApp number"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Expert assigned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Expert assigned successfully"),
     *             @OA\Property(property="expert_id", type="integer", example=3)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Lead or expert not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Lead not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function assignExpert(Request $request)
    {
        $request->validate([
            'customer_id'     => 'required|integer|exists:diet_leads,id',
            'whatsapp_number' => 'required|string',
        ]);

        // نرمال سازی شماره
        $phone = $this->normalizePhone($request->whatsapp_number);

        // پیدا کردن لید
        $lead = DietLead::find($request->customer_id);
        if (!$lead) {
            return response()->json([
                'success' => false,
                'message' => 'Lead not found'
            ], 404);
        }

        // پیدا کردن کارشناس با حالت‌های مختلف شماره
        $expert = User::where(function ($query) use ($phone) {
            $query->where('phone', $phone)
                ->orWhere('phone', ltrim($phone, '+'))
                ->orWhere('phone', preg_replace('/^\+?98/', '0', $phone));
        })->first();

        if (!$expert) {
            return response()->json([
                'success' => false,
                'message' => 'Expert not found'
            ], 404);
        }

        // ثبت کارشناس
        $lead->expert_id = $expert->id;
        $lead->save();

        return response()->json([
            'success' => true,
            'message' => 'Expert assigned successfully',
            'expert_id' => $expert->id
        ]);
    }



    /**
     * @OA\Get(
     *     path="/api/admin/diet-leads/source-report",
     *     summary="گزارش لیدها بر اساس منبع",
     *     description="تعداد لیدها بر اساس source شامل آمار روزانه، هفتگی، ماهانه و کل به همراه تعداد تماس‌نگرفته‌ها",
     *     tags={"Diet Leads Report"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="گزارش با موفقیت دریافت شد",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="source", type="string", example="Instagram"),
     *
     *                 @OA\Property(property="total", type="integer", example=120),
     *                 @OA\Property(property="total_not_contacted", type="integer", example=35),
     *
     *                 @OA\Property(property="today_total", type="integer", example=5),
     *                 @OA\Property(property="today_not_contacted", type="integer", example=2),
     *
     *                 @OA\Property(property="week_total", type="integer", example=18),
     *                 @OA\Property(property="week_not_contacted", type="integer", example=6),
     *
     *                 @OA\Property(property="month_total", type="integer", example=60),
     *                 @OA\Property(property="month_not_contacted", type="integer", example=15)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="عدم دسترسی - نیاز به احراز هویت"
     *     )
     * )
     */
    public function sourceReport()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'دسترسی غیرمجاز.'], 401);
        }
        $today = Carbon::today()->toDateString();
        $weekStart = Carbon::now()->startOfWeek()->toDateTimeString();
        $monthStart = Carbon::now()->startOfMonth()->toDateTimeString();

        $user = Auth::user();

        $results = collect();

        // =========================
        // گزارش بر اساس source
        // =========================
        $sourceQuery = DietLead::query();

        if (!$user->isAdmin()) {
            $sourceQuery->where('expert_id', $user->id);
        }

        $sources = $sourceQuery->select(

                DB::raw("source"),

                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN status = 0 OR status IS NULL THEN 1 ELSE 0 END) as total_not_contacted"),

                DB::raw("SUM(CASE WHEN DATE(created_at) = '{$today}' THEN 1 ELSE 0 END) as today_total"),
                DB::raw("SUM(CASE WHEN DATE(created_at) = '{$today}' AND (status = 0 OR status IS NULL) THEN 1 ELSE 0 END) as today_not_contacted"),

                DB::raw("SUM(CASE WHEN created_at >= '{$weekStart}' THEN 1 ELSE 0 END) as week_total"),
                DB::raw("SUM(CASE WHEN created_at >= '{$weekStart}' AND (status = 0 OR status IS NULL) THEN 1 ELSE 0 END) as week_not_contacted"),

                DB::raw("SUM(CASE WHEN created_at >= '{$monthStart}' THEN 1 ELSE 0 END) as month_total"),
                DB::raw("SUM(CASE WHEN created_at >= '{$monthStart}' AND (status = 0 OR status IS NULL) THEN 1 ELSE 0 END) as month_not_contacted")
            )
            ->groupBy('source')
            ->get();

        $results = $results->merge($sources);

        // =========================
        // اگر مدیر بود → کارشناسان هم اضافه شود
        // =========================
        // echo $user->isAdmin();

        if ($user->isAdmin()) {

            $experts = DietLead::query()
                ->join('diet_users', 'diet_users.id', '=', 'diet_leads.expert_id')
                ->select(

                    DB::raw("diet_users.last_name as source"),

                    DB::raw("COUNT(*) as total"),
                    DB::raw("SUM(CASE WHEN status = 0 OR status IS NULL THEN 1 ELSE 0 END) as total_not_contacted"),

                    DB::raw("SUM(CASE WHEN DATE(diet_leads.created_at) = '{$today}' THEN 1 ELSE 0 END) as today_total"),
                    DB::raw("SUM(CASE WHEN DATE(diet_leads.created_at) = '{$today}' AND (status = 0 OR status IS NULL) THEN 1 ELSE 0 END) as today_not_contacted"),

                    DB::raw("SUM(CASE WHEN diet_leads.created_at >= '{$weekStart}' THEN 1 ELSE 0 END) as week_total"),
                    DB::raw("SUM(CASE WHEN diet_leads.created_at >= '{$weekStart}' AND (status = 0 OR status IS NULL) THEN 1 ELSE 0 END) as week_not_contacted"),

                    DB::raw("SUM(CASE WHEN diet_leads.created_at >= '{$monthStart}' THEN 1 ELSE 0 END) as month_total"),
                    DB::raw("SUM(CASE WHEN diet_leads.created_at >= '{$monthStart}' AND (status = 0 OR status IS NULL) THEN 1 ELSE 0 END) as month_not_contacted")
                )
                ->groupBy('diet_users.id')
                ->get();
            //echo $this->getQuery($experts);
            //exit;
            $results = $results->merge($experts);
        }

        return $results->sortByDesc('total')->values();
    }


    /**
     * @OA\Post(
     *   path="/api/lead/register1",
     *     summary="ثبت اطلاعات اولیه کاربر برای رژیم غذایی",
     *     description="این متد اطلاعات اولیه کاربر (کشور، شماره تلفن، جنسیت، وزن و غیره) را دریافت و در جدول diet_leads ذخیره می‌کند.",
     *     tags={"Diet Leads"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Country Code", type="integer", example=98, description="کد کشور به‌صورت عددی (مثلاً 98 برای ایران)"),
     *             @OA\Property(property="Country Name", type="string", example="Iran", description="نام کشور مطابق لیست داخلی"),
     *             @OA\Property(property="Phone Number", type="string", example="9123456789", description="شماره تلفن بدون صفر ابتدایی و با ارقام انگلیسی"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}, example="0", description="جنسیت کاربر"),
     *             @OA\Property(property="weight", type="number", format="float", example=80, description="وزن به کیلوگرم"),
     *             @OA\Property(property="height", type="integer", example=175, description="قد به سانتی‌متر"),
     *             @OA\Property(property="age", type="integer", example=32, description="سن کاربر"),
     *             @OA\Property(property="Additional Information", type="string", example="Has been on keto diet before", description="توضیحات یا سابقه رژیم غذایی کاربر")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="اطلاعات با موفقیت ثبت شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lead created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="phone", type="string", example="9123456789"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="weight", type="number", example=80),
     *                 @OA\Property(property="height", type="integer", example=175),
     *                 @OA\Property(property="country", type="string", example="Iran"),
     *                 @OA\Property(property="code", type="integer", example=98),
     *                 @OA\Property(property="age", type="integer", example=32),
     *                 @OA\Property(property="notes", type="string", example="Has been on keto diet before"),
     *                 @OA\Property(property="ip_address", type="string", example="192.168.1.10")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="Phone Number", type="array", @OA\Items(type="string", example="The Phone Number field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function register1(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'Country Code' => 'required|numeric',
            'Country Name' => 'nullable|string|max:100',
            'Phone Number' => 'required|string|max:20',
            'gender' => 'nullable|in:male,female',
            'weight' => 'nullable|numeric|min:20|max:300',
            'height' => 'nullable|integer|min:50|max:250',
            'age' => 'nullable|integer|min:1|max:120',
            'Additional Information' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        $mapped = [
            'code'       => $data['Country Code'] ?? null,
            'country'    => $data['Country Name'] ?? null,
            'phone'      => $data['Phone Number'],
            'gender'     => $data['gender'] ?? null,
            'weight'     => $data['weight'] ?? null,
            'height'     => $data['height'] ?? null,
            'age'        => $data['age'] ?? null,
            'notes'      => $data['Additional Information'] ?? null,
            'ip_address' => $request->ip(),
            'source'     => 'source1',
        ];

        $lead = DietLead::create($mapped);

        // --------------------
        // ساخت متن پیام (PHP صحیح)
        // --------------------
        $expertName = 'الأخصائية'; // اگر داینامیک شد، از DB بخون
        $sourceName = $lead->source === 'di3t.club' ? 'di3t_club' : $lead->source;

        $message = "هلا وغلا 🤍
        معك الأخصائية {$expertName} من مركز {$sourceName}

        ✅ وصلتني بياناتك كاملة

        ⚖️ الوزن: " . ($lead->weight ?? '-') . "
        📏 الطول: " . ($lead->height ?? '-') . "
        🗓 العمر: " . ($lead->age ?? '-') . "
        🩺 الحالة الصحية: " . ($lead->notes ?? '-') . "

        للتأكيد والانتقال للخطوة التالية:

        *أرسل الرقم 1*";

        // --------------------
        // ارسال واتساپ
        // --------------------
        $this->whatsappService->sendMessage([
            'to'      => $lead->phone,
            'message' => $message,
            'tag'     => 'lead_register'
        ]);

        return response()->json([
            'message' => 'Lead created successfully',
            'data'    => $lead
        ], 201);
    }


    /**
     * @OA\Post(
     *   path="/api/lead/register2",
     *     summary="ثبت اطلاعات اولیه کاربر برای رژیم غذایی",
     *     description="این متد اطلاعات اولیه کاربر (کشور، شماره تلفن، جنسیت، وزن و غیره) را دریافت و در جدول diet_leads ذخیره می‌کند.",
     *     tags={"Diet Leads"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="Country Code", type="integer", example=98, description="کد کشور به‌صورت عددی (مثلاً 98 برای ایران)"),
     *             @OA\Property(property="Country Name", type="string", example="Iran", description="نام کشور مطابق لیست داخلی"),
     *             @OA\Property(property="Phone Number", type="string", example="9123456789", description="شماره تلفن بدون صفر ابتدایی و با ارقام انگلیسی"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}, example="male", description="جنسیت کاربر"),
     *             @OA\Property(property="weight", type="number", format="float", example=80, description="وزن به کیلوگرم"),
     *             @OA\Property(property="height", type="integer", example=175, description="قد به سانتی‌متر"),
     *             @OA\Property(property="age", type="integer", example=32, description="سن کاربر"),
     *             @OA\Property(property="Additional Information", type="string", example="Has been on keto diet before", description="توضیحات یا سابقه رژیم غذایی کاربر")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="اطلاعات با موفقیت ثبت شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lead created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="phone", type="string", example="9123456789"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="weight", type="number", example=80),
     *                 @OA\Property(property="height", type="integer", example=175),
     *                 @OA\Property(property="country", type="string", example="Iran"),
     *                 @OA\Property(property="code", type="integer", example=98),
     *                 @OA\Property(property="age", type="integer", example=32),
     *                 @OA\Property(property="notes", type="string", example="Has been on keto diet before"),
     *                 @OA\Property(property="ip_address", type="string", example="192.168.1.10")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="خطای اعتبارسنجی",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="Phone Number", type="array", @OA\Items(type="string", example="The Phone Number field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function register2(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Country Code' => 'required|numeric',
            'Country Name' => 'nullable|string|max:100',
            'Phone Number' => 'required|string|max:20',
            'gender' => 'nullable|in:male,female',
            'weight' => 'nullable|numeric|min:20|max:300',
            'height' => 'nullable|integer|min:50|max:250',
            'age' => 'nullable|integer|min:1|max:120',
            'Additional Information' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // نگاشت فیلدهای ورودی به فیلدهای دیتابیس
        $mapped = [
            'code' => $data['Country Code'] ?? null,
            'country' => $data['Country Name'] ?? null,
            'phone' => $data['Phone Number'] ?? null,
            'gender' => $data['gender'] ?? null,
            'weight' => $data['weight'] ?? null,
            'height' => $data['height'] ?? null,
            'age' => $data['age'] ?? null,
            'notes' => $data['Additional Information'] ?? null,
            'ip_address' => $request->ip(),
            'source' => 'source2',
        ];

        $lead = DietLead::create($mapped);

        return response()->json([
            'message' => 'Lead created successfully',
            'data' => $lead
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/diet-leads",
     *     summary="دریافت لیست لیدها با امکان فیلتر و صفحه‌بندی",
     *     tags={"Diet Leads"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="جستجو بر اساس شماره موبایل",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="gender",
     *         in="query",
     *         description="جنسیت (male/female)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="age",
     *         in="query",
     *         description="سن",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="وضعیت لید",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         description="کشور",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="pagesize",
     *         in="query",
     *         description="تعداد آیتم‌ها در هر صفحه (پیش‌فرض: 20)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(
     *             @OA\Property(property="result", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="height", type="integer"),
     *                     @OA\Property(property="weight", type="integer"),
     *                     @OA\Property(property="phone", type="string"),
     *                     @OA\Property(property="country", type="string"),
     *                     @OA\Property(property="code", type="string"),
     *                     @OA\Property(property="age", type="integer"),
     *                     @OA\Property(property="source", type="string"),
     *                     @OA\Property(property="status", type="integer"),
     *                     @OA\Property(property="statusValue", type="string"),
     *                     @OA\Property(property="user_status", type="integer"),
     *                     @OA\Property(property="userStatusValue", type="string"),
     *                     @OA\Property(property="expert_id", type="integer"),
     *                     @OA\Property(property="expert", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="notes", type="string"),
     *                 )
     *             ),
     *             @OA\Property(property="totalCount", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="عدم دسترسی"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $pageSize = (int)($request->pagesize ?? 20);
        $query = DietLead::query();
        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%");
        }
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }
        if ($request->filled('age')) {
            $query->where('age', $request->age);
        }
        if ($request->filled('status')) {

        if ($request->status == 0) {
                $query->where(function ($q) {
                    $q->where('status', 0)
                    ->orWhereNull('status');
                });
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('country')) {
            $query->where('country', $request->country);
        }
        // echo $this->getQuery($query);
        // exit;
        $totalCount = $query->count();
        $users = $query->orderBy('id', 'desc')->paginate($pageSize);
        $users = array_map(function ($user) {
            return [
                'id' => $user->id,
                'height' => $user->height,
                'weight' => $user->weight,
                'phone' => $user->phone,
                'country' => $user->country,
                'code' => $user->code,
                'age' => $user->age,
                'source' => $user->source,
                'status' => $user->status,
                'statusValue' => $user->statusValue(),
                'user_status' => $user->user_status,
                'userStatusValue' => $user->userStatusValue(),
                'expert_id' => $user->expert_id,
                'expert' => optional($user->expert)->fullname(),
                'created_at' => $user->created_at,
                'notes' => $user->notes,
            ];
        }, $users->items());
        return response()->json([
            'result' => $users,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/diet-leads",
     *     summary="ایجاد لید جدید (بدون نیاز به ورود)",
     *     description="این سرویس برای ثبت لید کاربران عمومی استفاده می‌شود و نیاز به ورود ندارد.",
     *     tags={"Diet Leads"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone"},
     *             @OA\Property(property="phone", type="string", example="+989123456789", description="شماره تماس کاربر"),
     *             @OA\Property(property="gender", type="string", enum={"male", "female"}, example="male", description="جنسیت کاربر"),
     *             @OA\Property(property="height", type="integer", example=175, description="قد (سانتی‌متر)"),
     *             @OA\Property(property="weight", type="integer", example=80, description="وزن (کیلوگرم)"),
     *             @OA\Property(property="country", type="string", example="Iran", description="کشور کاربر"),
     *             @OA\Property(property="code", type="string", example="0081", description="کد مرجع یا کد پیگیری"),
     *             @OA\Property(property="age", type="integer", example=30, description="سن کاربر"),
     *             @OA\Property(property="source", type="string", example="Instagram", description="منبع لید (مثلاً تبلیغ، اینستاگرام و غیره)"),
     *             @OA\Property(property="status", type="integer", example=1, description="وضعیت لید"),
     *             @OA\Property(property="user_status", type="integer", example=1, description="وضعیت کاربر لید"),
     *             @OA\Property(property="expert_id", type="integer", example=0, description="شناسه کارشناس اختصاص‌داده‌شده"),
     *             @OA\Property(property="notes", type="string", example="علاقه‌مند به برنامه رژیم آنلاین", description="توضیحات اضافی کاربر")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="لید با موفقیت ایجاد شد",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=12),
     *             @OA\Property(property="phone", type="string", example="+989123456789"),
     *             @OA\Property(property="gender", type="string", example="male"),
     *             @OA\Property(property="height", type="integer", example=175),
     *             @OA\Property(property="weight", type="integer", example=80),
     *             @OA\Property(property="ip_address", type="string", example="192.168.1.10"),
     *             @OA\Property(property="created_at", type="string", example="2025-10-26T13:43:22.000000Z")
     *         )
     *     ),
     *     @OA\Response(response=422, description="خطای اعتبارسنجی")
     * )
     */

    public function store(Request $request)
    {
        // در صورت وجود کاربر لاگین‌شده
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'gender' => 'nullable|in:male,female',
            'height' => 'nullable|integer',
            'weight' => 'nullable|integer',
            'country' => 'nullable|string',
            'code' => 'nullable|string',
            'age' => 'nullable|integer',
            'source' => 'nullable|string',
            'status' => 'nullable|integer',
            'user_status' => 'nullable|integer',
            'expert_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lead = DietLead::create(array_merge(
            $validator->validated(),
            [
                'ip_address' => $request->ip(),
                'user_id' => $user?->id // اگر کاربر لاگین کرده بود، ذخیره میشه
            ]
        ));

        // --------------------
        // ساخت متن پیام (PHP صحیح)
        // --------------------
        $expertName = 'الأخصائية'; // اگر داینامیک شد، از DB بخون
        $sourceName = $lead->source === 'di3t.club' ? 'di3t_club' : $lead->source;

        $message = "هلا وغلا
معك الأخصائية {$expertName} من مركز {$sourceName}

✅ وصلتني بياناتك كاملة

⚖️ الوزن: " . ($lead->weight ?? '-') . "
📏 الطول: " . ($lead->height ?? '-') . "
🗓 العمر: " . ($lead->age ?? '-') . "
🩺 الحالة الصحية: " . ($lead->notes ?? '-') . "

لتأكيد والانتقال للخطوة التالية:

*أرسل الرقم 1*";

// حذف کاراکترهای 4 بایتی (مثل emoji) برای جلوگیری از خطای MySQL
$message = mb_convert_encoding($message, 'UTF-8', 'UTF-8'); // اطمینان از UTF-8
$message = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $message);

        // --------------------
        // ارسال واتساپ
        // --------------------
        $this->whatsappService->sendMessage([
            'to'      => $lead->phone,
            'message' => $message,
            'tag'     => 'lead_register'
        ]);


        return response()->json($lead, 201);
    }

    /**
     * @OA\Get(
     *   path="/api/diet-leads/{id}",
     *   summary="نمایش جزئیات لید",
     *   tags={"Diet Leads"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="موفق"),
     *   @OA\Response(response=404, description="یافت نشد"),
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead = DietLead::find($id);
        if (!$lead) {
            return response()->json(['message' => 'لید یافت نشد'], 404);
        }
        return response()->json($lead);
    }

    /**
     * @OA\Put(
     *   path="/api/diet-leads-edit",
     *   summary="ویرایش لید بر اساس شماره تلفن",
     *   tags={"Diet Leads"},
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(
     *           type="object",
     *           required={"phone_number"},
     *           @OA\Property(property="phone_number", type="string", example="9123456789", description="شماره تلفن بدون صفر ابتدایی"),
     *           @OA\Property(property="age", type="integer", example=32, description="سن کاربر"),
     *           @OA\Property(property="additional_info", type="string", example="Has been on keto diet before", description="سابقه رژیم یا توضیحات کاربر")
     *       )
     *   ),
     *   @OA\Response(response=200, description="لید با موفقیت بروزرسانی شد"),
     *   @OA\Response(response=404, description="لید با این شماره یافت نشد"),
     *   @OA\Response(response=422, description="خطای اعتبارسنجی ورودی‌ها")
     * )
     */
    public function edit(Request $request)
    {
        // ✅ پیدا کردن لید بر اساس شماره تلفن
        $lead = DietLead::where('phone', $request->phone_number)->first();

        if (!$lead) {
            return response()->json(['message' => 'لید با این شماره یافت نشد'], 404);
        }

        // ✅ اعتبارسنجی داده‌ها
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:20',
            'age' => 'nullable|integer|min:1|max:120',
            'additional_info' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ✅ نگاشت داده‌ها برای ذخیره
        $data = $validator->validated();

        $mapped = [

            'phone' => $data['phone_number'] ?? null,
            'age' => $data['age'] ?? null,
            'notes' => $data['additional_info'] ?? null,
        ];

        // ✅ بروزرسانی لید
        $lead->update($mapped);

        return response()->json([
            'message' => 'لید با موفقیت بروزرسانی شد',
            'data' => $lead
        ]);
    }



    /**
     * @OA\Put(
     *   path="/api/diet-leads/{id}",
     *   summary="ویرایش لید",
     *   tags={"Diet Leads"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *       required=true,
     *       @OA\JsonContent(ref="#/components/schemas/DietLead")
     *   ),
     *   @OA\Response(response=200, description="بروزرسانی شد"),
     * )
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead = DietLead::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'phone' => 'sometimes|required|string|max:20',
            'gender' => 'nullable|in:male,female',
            'height' => 'nullable|integer|min:50|max:250',
            'weight' => 'nullable|integer|min:20|max:300',
            'country' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:100',
            'age' => 'nullable|integer|min:1|max:120',
            'source' => 'nullable|string|max:100',
            'status' => 'nullable|integer',
            'user_status' => 'nullable|integer',
            'expert_id' => 'nullable',
            'notes' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $lead->update($validator->validated());
        return response()->json($lead);
    }

    /**
     * @OA\Delete(
     *   path="/api/diet-leads/{id}",
     *   summary="حذف لید",
     *   tags={"Diet Leads"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="حذف شد"),
     * )
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $lead = DietLead::findOrFail($id);
        $lead->delete();
        return response()->json(['message' => 'حذف شد']);
    }
}
