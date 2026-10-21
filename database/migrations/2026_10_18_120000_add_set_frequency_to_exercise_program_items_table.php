<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercise_program_items', function (Blueprint $table): void {
            // ست و تکرار دستی؛ در صورت خالی بودن هنگام تخصیص به کاربر به صورت تصادفی تعیین می‌شود
            $table->unsignedTinyInteger('set')->nullable()->after('day');
            $table->unsignedSmallInteger('frequency')->nullable()->after('set');
        });
    }

    public function down(): void
    {
        Schema::table('exercise_program_items', function (Blueprint $table): void {
            $table->dropColumn(['set', 'frequency']);
        });
    }
};
