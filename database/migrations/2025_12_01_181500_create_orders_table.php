<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('user_questions')->onDelete('cascade');
            $table->foreignId('offer_id')->constrained('question_offers')->onDelete('cascade');
            $table->foreignId('asker_id')->constrained('users')->onDelete('cascade'); // السائل
            $table->foreignId('answerer_id')->constrained('users')->onDelete('cascade'); // المجيب
            $table->decimal('price', 10, 2); // السعر النهائي
            $table->integer('response_time'); // مدة الرد بالدقائق
            $table->enum('status', [
                'pending',      // في انتظار الإجابة
                'answered',     // تم الرد
                'cancelled',    // ملغي من السائل
                'expired'       // انتهى الوقت
            ])->default('pending');
            $table->text('answer_text')->nullable(); // الإجابة النصية
            $table->string('answer_image')->nullable(); // صورة الإجابة
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // وقت انتهاء المدة
            $table->timestamps();

            $table->index('status');
            $table->index('asker_id');
            $table->index('answerer_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};