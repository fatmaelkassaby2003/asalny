<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'paid', 'failed'])
                ->default('pending')
                ->after('status')
                ->comment('حالة الدفع: pending (بانتظار الدفع), paid (مدفوع), failed (فشل)');
            
            $table->string('payment_method')->nullable()
                ->after('payment_status')
                ->comment('طريقة الدفع: wallet أو fawaterak');
            
            $table->string('invoice_id')->nullable()
                ->after('payment_method')
                ->comment('معرف الفاتورة من Fawaterak');
            
            $table->timestamp('paid_at')->nullable()
                ->after('invoice_id')
                ->comment('تاريخ الدفع');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_method', 'invoice_id', 'paid_at']);
        });
    }
};
