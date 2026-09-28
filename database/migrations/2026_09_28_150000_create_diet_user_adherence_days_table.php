<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // هر روزی که کاربر برنامه غذایی‌اش را چک کند یک ردیف (حداکثر یکی در روز)
        Schema::create('diet_user_adherence_days', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('user_weekly_id')->nullable();
            $table->date('date');
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index('user_weekly_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_user_adherence_days');
    }
};
