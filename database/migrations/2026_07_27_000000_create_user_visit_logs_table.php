<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_visit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('diet_users')->nullOnDelete();
            $table->string('page_url', 2048);
            $table->string('page_path', 1024)->nullable()->index();
            $table->string('page_title')->nullable();
            $table->string('referrer_url', 2048)->nullable();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('visited_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['user_id', 'visited_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_visit_logs');
    }
};
