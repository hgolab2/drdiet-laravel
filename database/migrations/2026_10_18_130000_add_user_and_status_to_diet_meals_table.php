<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_meals', function (Blueprint $table): void {
            // سازنده وعده؛ برای وعده‌های ساخته‌شده توسط ادمین null است
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            // pending | approved | rejected — وعده‌های قبلی همه تایید شده‌اند
            $table->string('status', 20)->default('approved')->after('user_id');

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('diet_meals', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['user_id', 'status']);
        });
    }
};
