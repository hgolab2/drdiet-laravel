<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diet_item', function (Blueprint $table): void {
            // مقدار ماکرو (گرم) در هر گرم از آیتم
            $table->decimal('proteinGram', 8, 4)->nullable()->after('caloriesGram');
            $table->decimal('carbsGram', 8, 4)->nullable()->after('proteinGram');
            $table->decimal('fatGram', 8, 4)->nullable()->after('carbsGram');
        });
    }

    public function down(): void
    {
        Schema::table('diet_item', function (Blueprint $table): void {
            $table->dropColumn(['proteinGram', 'carbsGram', 'fatGram']);
        });
    }
};
