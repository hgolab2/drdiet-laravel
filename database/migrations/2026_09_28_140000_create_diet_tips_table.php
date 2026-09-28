<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // نکات کارشناس که بر اساس نوع رژیم (diet_type_id) به کاربر نمایش داده می‌شوند
        Schema::create('diet_tips', function (Blueprint $table): void {
            $table->id();
            $table->text('text');
            // عدد کمتر = اولویت بالاتر
            $table->unsignedInteger('priority')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'priority']);
        });

        // نوع رژیم‌های هر نکته؛ نکته بدون ردیف در این جدول برای همه نمایش داده می‌شود
        Schema::create('diet_tip_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('diet_tip_id');
            $table->unsignedInteger('diet_type_id');

            $table->unique(['diet_tip_id', 'diet_type_id']);
            $table->index('diet_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_tip_types');
        Schema::dropIfExists('diet_tips');
    }
};
