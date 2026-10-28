<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_users', function (Blueprint $table): void {
            // تاریخ ثبت هفته بارداری؛ برای محاسبه خودکار هفته فعلی بارداری
            $table->timestamp('pregnancy_week_updated_at')->nullable()->after('pregnancy_week');
        });
    }

    public function down(): void
    {
        Schema::table('diet_users', function (Blueprint $table): void {
            $table->dropColumn('pregnancy_week_updated_at');
        });
    }
};
