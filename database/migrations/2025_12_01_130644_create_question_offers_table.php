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
        Schema::create('question_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('user_questions')->onDelete('cascade');
            $table->foreignId('answerer_id')->constrained('users')->onDelete('cascade'); // المجيب
            $table->foreignId('asker_id')->constrained('users')->onDelete('cascade'); // السائل (صاحب السؤال)
            $table->decimal('price', 10, 2); // السعر المقترح
            $table->integer('response_time'); // مدة الرد بالدقائق
            $table->text('note')->nullable(); // ملاحظة اختيارية من المجيب
            $table->enum('status', ['pending', 'accepted', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            // فهرس مركب لمنع تكرار العرض من نفس المجيب على نفس السؤال
            $table->unique(['question_id', 'answerer_id']);
            
            // فهارس للبحث السريع
            $table->index('status');
            $table->index('asker_id');
            $table->index('answerer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_offers');
    }
};