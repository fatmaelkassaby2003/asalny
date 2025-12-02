<?php

// database/migrations/2024_01_01_000005_create_question_views_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('user_questions')->onDelete('cascade');
            $table->foreignId('viewer_id')->constrained('users')->onDelete('cascade'); // المجيب اللي شاف السؤال
            $table->foreignId('asker_id')->constrained('users')->onDelete('cascade'); // السائق صاحب السؤال
            $table->timestamp('viewed_at'); // وقت المشاهدة
            $table->timestamps();
            
            // Index للبحث السريع
            $table->index('question_id');
            $table->index('viewer_id');
            $table->index('asker_id');
            
            // منع التكرار (كل مجيب يشوف السؤال مرة واحدة فقط)
            $table->unique(['question_id', 'viewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_views');
    }
};