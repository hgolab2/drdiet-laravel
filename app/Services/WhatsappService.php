<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class WhatsappService
{
    /**
     * ارسال پیام واتساپ با قوانین فاصله، صف صبحگاهی و محدودیت روزانه
     *
     * @param array $data
     *      - to: شماره دریافت‌کننده
     *      - message: متن پیام
     *      - tag: (اختیاری) تگ پیام
     * @return array
     */
    public function sendMessage(array $data): array
    {
        // بررسی تعداد پیام‌های امروز
        $todayCount = WhatsappMessage::where('type', 'sent')
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= 100) {
            return [
                'success' => false,
                'error'   => 'Daily WhatsApp limit reached (100 messages)',
            ];
        }

        $now = Carbon::now()->addMinutes(5); // حداقل ۵ دقیقه بعد از الان

        // آخرین پیام ارسالی
        $lastMessage = WhatsappMessage::where('type', 'sent')
            ->latest('created_at')
            ->first();

        // تعیین زمان نهایی ارسال
        if ($lastMessage && $lastMessage->created_at) {
            $lastPlusFive = Carbon::parse($lastMessage->created_at)->addMinutes(5);

            // انتخاب بزرگ‌ترین زمان: حداقل ۵ دقیقه بعد از الان
            $sendDate = $lastPlusFive->greaterThan($now)
                ? $lastPlusFive
                : $now;
        } else {
            $sendDate = $now;
        }

        // جلوگیری از ارسال بین 00:00 تا 07:00 و صف‌بندی
        if ($sendDate->hour < 7) {
            $queueIndex = WhatsappMessage::where('type', 'sent')
                ->whereDate('created_at', $sendDate->toDateString())
                ->whereTime('created_at', '>=', '07:00:00')
                ->count();

            $sendDate = Carbon::parse($sendDate->toDateString())
                ->setTime(7, 0, 0)
                ->addMinutes($queueIndex * 5);
        }

        // آماده‌سازی داده‌ها برای API
        $apiUrl = 'https://api.autochat.ir/api/v1/whatsapp/send-message2';
        $token  = env('AUTOCHAT_TOKEN');

        $formData = [
            'to'      => $data['to'],
            'message' => $data['message'],
            'token'   => $token,
            'date'    => $sendDate->format('Y-m-d H:i:s'),
        ];

        if (!empty($data['tag'])) {
            $formData['tag'] = $data['tag'];
        }

        // ارسال پیام
        $response = Http::asForm()->post($apiUrl, $formData);

        // ذخیره پیام در دیتابیس
        WhatsappMessage::create([
            'message_id'   => 'out_' . uniqid(),
            'type'         => 'sent',
            'from'         => null,
            'to'           => $data['to'],
            'message_type' => 'chat',
            'body'         => $data['message'],
            'status'       => $response->successful() ? 1 : 0,
            'has_media'    => false,
            'payload'      => $response->json(),
            'created_at'   => $sendDate,
        ]);

        return [
            'success'  => $response->successful(),
            'send_at'  => $sendDate->format('Y-m-d H:i:s'),
            'response' => $response->json(),
        ];
    }
}
