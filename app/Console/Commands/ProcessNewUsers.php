<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class ProcessNewUsers extends Command
{
    protected $signature = 'users:process-new';
    protected $description = 'اجرای عملیات برای کاربران جدید';

    public function handle()
    {
        // مثال: کاربران ثبت‌نام‌شده ۳۰ دقیقه اخیر
        $users = User::where('created_at', '>=', now()->subMinutes(30))
            ->where('processed', 0) // خیلی مهم
            ->get();

        foreach ($users as $user) {
            // 🔹 عملیات موردنظر تو
            // مثلا:
            // $user->giveWelcomePoints();
            // $user->sendWelcomeNotification();

            $user->update([
                'processed' => 1
            ]);
        }

        $this->info('New users processed: ' . $users->count());
    }
}
