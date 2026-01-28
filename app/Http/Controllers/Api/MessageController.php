<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/webhook/whatsapp",
     *     operationId="receiveWhatsappWebhook",
     *     summary="دریافت پیام واتساپ (Webhook)",
     *     description="دریافت پیام‌های ورودی واتساپ از طریق وب‌هوک. نتیجه می‌تواند ذخیره، تکراری یا نادیده گرفته شده باشد.",
     *     tags={"Whatsapp Webhook"},
     *
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=false,
     *         description="JWT Token برای اعتبارسنجی وب‌هوک",
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type","data"},
     *             @OA\Property(property="type", type="string", example="message"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 required={"id","from","to","type","status","hasMedia"},
     *                 @OA\Property(property="id", type="string", example="3EB02B006EF2868845016A"),
     *                 @OA\Property(property="from", type="string", example="989132222222@c.us"),
     *                 @OA\Property(property="to", type="string", example="989131111111@c.us"),
     *                 @OA\Property(property="type", type="string", example="chat"),
     *                 @OA\Property(property="fromMe", type="boolean", example=false),
     *                 @OA\Property(property="body", type="string", example="تست"),
     *                 @OA\Property(property="status", type="integer", example=1),
     *                 @OA\Property(property="hasMedia", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     nullable=true,
     *                     example="https://example.com/file.jpg"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="نتیجه پردازش وب‌هوک",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="stored")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="duplicate")
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="status", type="string", example="ignored")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        if (!isset($payload['type']) || $payload['type'] !== 'message') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $data = $payload['data'];

        // جلوگیری از ثبت تکراری پیام
        if (WhatsappMessage::where('message_id', $data['id'])->exists()) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        $localFilePath = null;

        // 📎 اگر پیام فایل داشت
        if (!empty($data['hasMedia']) && !empty($data['file'])) {

            $uploadDir = public_path('uploads/whatsapp');

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileContent = Http::get($data['file'])->body();

            $fileName = uniqid('wa_') . '_' . basename(parse_url($data['file'], PHP_URL_PATH));

            file_put_contents($uploadDir . '/' . $fileName, $fileContent);

            $localFilePath = 'uploads/whatsapp/' . $fileName;
        }

        WhatsappMessage::create([
            'message_id'      => $data['id'],
            'type'            => $payload['type'],
            'from'            => $data['from'],
            'to'              => $data['to'],
            'message_type'    => $data['type'] ?? null,
            'body'            => $data['body'] ?? null,
            'status'          => $data['status'] ?? null,
            'has_media'       => $data['hasMedia'] ?? false,
            'file_url'        => $data['file'] ?? null,
            'local_file_path' => $localFilePath,
            'payload'         => $payload,
        ]);

        return response()->json(['status' => 'stored'], 200);
    }



    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-message",
     *     operationId="sendWhatsappMessage",
     *     summary="ارسال پیام واتساپ",
     *     description="این متد پیام واتساپ را از طریق سرویس AutoChat ارسال می‌کند.",
     *     tags={"Whatsapp Service"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"to","message"},
     *                 @OA\Property(property="to", type="string", description="شماره گیرنده به فرمت +98...", example="+989123456789"),
     *                 @OA\Property(property="message", type="string", description="متن پیام", example="سلام تست"),
     *                 @OA\Property(property="date", type="string", format="date-time", description="زمان ارسال برنامه‌ریزی شده (اختیاری)", example="2026-01-08 14:30:00"),
     *                 @OA\Property(property="tag", type="string", description="تگ پیام (اختیاری)", example="Test123")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="ارسال موفق یا پاسخ API AutoChat",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="response", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="خطا در ارسال پیام",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="exception"),
     *             @OA\Property(property="message", type="string", example="Error message")
     *         )
     *     )
     * )
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
            'date' => 'nullable|date_format:Y-m-d H:i:s',
            'tag' => 'nullable|string',
        ]);

        $apiUrl = "https://api.autochat.ir/api/v1/whatsapp/send-message";
        $token = env('AUTOCHAT_TOKEN');

        $formData = [
            'to' => $request->to,
            'message' => $request->message,
            'token' => $token,
        ];

        if ($request->has('date')) {
            $formData['date'] = $request->date;
        }

        if ($request->has('tag')) {
            $formData['tag'] = $request->tag;
        }

        try {
            $response = Http::asForm()->post($apiUrl, $formData);

            $status = $response->successful() ? 'success' : 'error';

            // ذخیره پیام ارسال‌شده تو جدول
            WhatsappMessage::create([
                'message_id' => 'out_' . uniqid(), // پیام خروجی، یک شناسه تصادفی بسازیم
                'type' => 'sent', // پیام ارسالی
                'from' => null, // چون پیام خروجیه
                'to' => $request->to,
                'message_type' => 'chat',
                'body' => $request->message,
                'status' => $status === 'success' ? 1 : 0,
                'has_media' => false,
                'file_url' => null,
                'local_file_path' => null,
                'payload' => $response->json(),
            ]);

            if ($status === 'success') {
                return response()->json([
                    'status' => 'success',
                    'response' => $response->json()
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'response' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'exception',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
