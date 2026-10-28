<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_weekly', function (Blueprint $table): void {
            // تعداد روزهای متفاوت برنامه؛ برنامه‌های قبلی همه ۷ روزه هستند
            $table->unsignedTinyInteger('days_count')->default(7)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('diet_weekly', function (Blueprint $table): void {
            $table->dropColumn('days_count');
        });
    }
};
