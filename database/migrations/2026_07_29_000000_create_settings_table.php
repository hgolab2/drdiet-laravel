<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            Schema::table('settings', function (Blueprint $table): void {
                if (! Schema::hasColumn('settings', 'key')) {
                    $table->string('key', 150)->nullable()->unique()->after('id');
                }

                if (! Schema::hasColumn('settings', 'value')) {
                    $table->json('value')->nullable()->after('key');
                }

                if (! Schema::hasColumn('settings', 'type')) {
                    $table->string('type', 50)->nullable()->after('value');
                }

                if (! Schema::hasColumn('settings', 'description')) {
                    $table->string('description', 500)->nullable()->after('type');
                }

                if (! Schema::hasColumn('settings', 'created_at') && ! Schema::hasColumn('settings', 'updated_at')) {
                    $table->timestamps();
                }
            });

            return;
        }

        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 150)->unique();
            $table->json('value')->nullable();
            $table->string('type', 50)->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};