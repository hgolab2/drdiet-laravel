<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="مدیریت تراکنش‌ها و پرداخت‌ها"
 * )
*/
class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/payments",
     *     tags={"Payments"},
     *     summary="نمایش لیست همه پرداخت‌ها",
     *     @OA\Response(response=200, description="موفق"),
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->hasRole('super_admin')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        $pageSize = (int)($request->pagesize ?? 20);
        $query = Payment::query();

        if ($request->filled('phone')) {
            $query->where('phone',  $request->phone);
        }

        $totalCount = $query->count();
        $items = $query->orderBy('id', 'desc')->paginate($pageSize);
        $items = array_map(function ($item) {
            return [
                'id' => $item->id,
                'token' => $item->id,
                'user_id' => $item->user_id,
                'amount' => $item->amount,
                'currency' => $item->currency,
                'status' => $item->status,
                'country' => $item->country,
                'phone' => $item->phone,
                'email' => $item->email,
                'name' => $item->name,
                'notes' => $item->notes,
            ];
        }, $items->items());

        return response()->json([
            'result' => $items,
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/payments",
     *     tags={"Payments"},
     *     summary="ایجاد پرداخت جدید",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount"},
     *             @OA\Property(property="amount", type="number", example="25.50"),
     *             @OA\Property(property="currency", type="string", example="OMR"),
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="phone", type="string", example="+96891234567"),
     *             @OA\Property(property="email", type="string", example="user@example.com"),
     *             @OA\Property(property="name", type="string", example="Ali Ahmed"),
     *             @OA\Property(property="notes", type="string", example="پرداخت تستی")
     *         )
     *     ),
     *     @OA\Response(response=201, description="ایجاد شد")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|max:10',
            'user_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:191',
            'email' => 'nullable|email|max:191',
            'name' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = Payment::create([
            'token' => uniqid('pay_'),
            'user_id' => $request->user_id,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'OMR',
            'status' => 'pending',
            'country' => $request->country,
            'phone' => $request->phone,
            'email' => $request->email,
            'name' => $request->name,
            'notes' => $request->notes,
        ]);

        return response()->json($payment, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/payments/{id}",
     *     tags={"Payments"},
     *     summary="ویرایش اطلاعات پرداخت",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="completed"),
     *             @OA\Property(property="notes", type="string", example="پرداخت انجام شد")
     *         )
     *     ),
     *     @OA\Response(response=200, description="به‌روزرسانی شد")
     * )
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);
        if (!$payment) {
            return response()->json(['message' => 'پرداخت یافت نشد'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:pending,processing,completed,failed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment->update($request->only(['status', 'notes']));

        return response()->json($payment);
    }

    /**
     * @OA\Delete(
     *     path="/api/payments/{id}",
     *     tags={"Payments"},
     *     summary="حذف پرداخت",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="حذف شد")
     * )
     */
    public function destroy($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'پرداخت یافت نشد'], 404);
        }

        $payment->delete();
        return response()->json(['message' => 'پرداخت حذف شد']);
    }

        /**
     * @OA\Get(
     *     path="/api/transactions/{token}",
     *     summary="ایجاد تراکنش جدید و انتقال خودکار به درگاه بانکی",
     *     description="با دریافت توکن سفارش، یک رکورد تراکنش جدید ایجاد می‌شود و کاربر به‌صورت خودکار به درگاه بانکی منتقل می‌گردد.",
     *     operationId="storeTransactionGet",
     *     tags={"Transactions"},
     *
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="توکن سفارش (Payment Token)",
     *         @OA\Schema(type="string", example="abc123xyz")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="فرم HTML برای انتقال خودکار به درگاه بانکی",
     *         @OA\MediaType(
     *             mediaType="text/html",
     *             @OA\Schema(type="string", example="<html><body onload='document.forms[0].submit()'>...</body></html>")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="سفارش یافت نشد",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="سفارش یافت نشد")
     *         )
     *     )
     * )
     */


    public function storeTransaction($token)
    {

        // پیدا کردن سفارش مربوطه
        $order = Payment::where('token', $token)->first();

        if (!$order) {
            return response()->json(['message' => 'سفارش یافت نشد'], 404);
        }

        // ساخت رکورد تراکنش
        $transaction = Transaction::create([
            'user_id' => $order->id,
            'payment_id' => $order->id,
            'amount' => $order->amount,
            'currency' => $order->currency ?? 'OMR',
            'status' => 'pending',
            'request_payload' => [
                'order_id' => $order->id,
                'amount' => $order->amount,
                'currency' => $order->currency ?? 'OMR',
                'return_url' => env('MUSCAT_RETURN_URL'),
                'callback_url' => env('MUSCAT_CALLBACK_URL'),
                'customer' => [
                    'phone' => $order->phone,
                    'email' => $order->email,
                    'name' => $order->name,
                ],
            ],
        ]);

        // آماده‌سازی payload برای ارسال به درگاه
        $payload = [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'currency' => $order->currency ?? 'OMR',
            'callback_url' => env('MUSCAT_CALLBACK_URL'),
            'customer_phone' => $order->phone,
            'customer_email' => $order->email,
        ];


        // به‌روزرسانی اطلاعات در request_payload برای شفافیت
        $transaction->request_payload = $payload;
        $transaction->save();

        // ایجاد فرم اتوماتیک برای انتقال به درگاه
        $endpoint = env('MUSCAT_ENDPOINT');


        $price =  $order->price * 0.38;
        $product_details = array();
        $p_detail				= array();
        $p_detail['name'] 		= 'اشتراك حمية دكتور دايت';
        $p_detail['image'] 		= ''; //needs filling
        $p_detail['price'] 		= $price; //needs filling
        $p_detail['quantity'] 	= 1; //needs filling
        $product_details[] 		= $p_detail;
        $products = json_encode( $product_details );

        $form = "
            <html>
            <body onload='document.forms[0].submit()'>
                <form method='POST' action='https://sub1.seyaaha.com/payment/'  id='send_order_detail'>
                    <input type='hidden' name='order_id' value='{$transaction->id}'>
                    <input type='hidden' name='product_details' value='{$products}'>
                    <input type='hidden' name='order_received_url' value='https://api.di3t-club.com/api/transactions/handleReturn'>
                    <input type='hidden' name='price' value='{$price}'/>
                    <input type='hidden' name='country' value='{$order->country}'/>
                    <input type='hidden' name='phoneNumber' value='{$order->phone}'/>
                    <input type='hidden' name='billing_phone'  value='{$order->phone}'/>
                    <input type='hidden' name='order_billing_first_name' value='{$order->name}'>
                    <input type='hidden' name='billing_email' value='{$order->email}'>


                    <noscript>
                        <p>در حال انتقال به درگاه بانکی هستید. اگر منتقل نشدید، دکمه زیر را بزنید:</p>
                        <button type='submit'>رفتن به بانک</button>
                    </noscript>
                </form>
            </body>
            </html>
        ";
        //dd($form);
        return response($form);
    }



    // صفحهٔ بازگشتی که کاربر را به آن redirect می‌کنند (GET یا POST)
    public function handleReturn(Request $request)
    {
        dd($request);
        // بانک ممکن است پارامترها را GET یا POST بفرستد
        $data = $request->all();
        Log::info('Payment return received', $data);

        // اگر بانک order_id می‌فرستد:
        $orderId = $request->input('order_id') ?? $request->input('merchant_order_id');
        $tx = Transaction::where('order_id', $orderId)->first();

        // فقط نمایش صفحهٔ مناسب؛ اما وضعیت تراکنش نهایی باید از callback سرور-به-سرور یا verify endpoint گرفته شود.
        return view('payments.return', ['data' => $data, 'transaction' => $tx]);
    }




}
