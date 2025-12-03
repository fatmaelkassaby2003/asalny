<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->enum('type', [
                'deposit',      // إيداع
                'withdraw',     // سحب
                'hold',         // حجز (عند قبول العرض)
                'release',      // تحرير (عند إلغاء الطلب)
                'transfer_in',  // تحويل وارد (للمجيب)
                'transfer_out'  // تحويل صادر (من السائل)
            ]);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};