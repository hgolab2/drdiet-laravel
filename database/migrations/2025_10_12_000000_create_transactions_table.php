<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // اگر کاربر لاگین بود
            $table->string('order_id')->unique(); // شناسه داخلی / مرجع تراکنش
            $table->string('payment_provider')->nullable(); // e.g. muscat_bank
            $table->decimal('amount', 12, 2); // مقدار (به واحد درخواستی)
            $table->string('currency', 10)->default('OMR');
            $table->enum('status', ['pending','processing','completed','failed','cancelled'])->default('pending');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->json('request_payload')->nullable();   // داده‌ای که برای درگاه فرستادیم
            $table->json('response_payload')->nullable();  // پاسخ درگاه (raw)
            $table->string('provider_transaction_id')->nullable(); // شناسه تراکنش از درگاه
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
