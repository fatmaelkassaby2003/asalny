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
        Schema::table('user_questions', function (Blueprint $table) {
            // إضافة column للأسئلة المتعددة (JSON)
            $table->json('questions')->nullable()->after('question');
            
            // ملاحظة: إذا كان questions موجود، نستخدمه. وإلا نستخدم question
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_questions', function (Blueprint $table) {
            $table->dropColumn('questions');
        });
    }
};
