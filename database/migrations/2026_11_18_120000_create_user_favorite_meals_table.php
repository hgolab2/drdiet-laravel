<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // وعده‌های علاقه‌مندی کاربر (دکمه قلب در مدال استبدال)
        Schema::create('user_favorite_meals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('meal_id');
            $table->timestamps();

            $table->unique(['user_id', 'meal_id']);
            $table->index('meal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_favorite_meals');
    }
};
