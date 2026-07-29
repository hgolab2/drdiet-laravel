<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_performance_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('diet_users')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('diet_users')->cascadeOnDelete();
            $table->text('description');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_performance_notes');
    }
};