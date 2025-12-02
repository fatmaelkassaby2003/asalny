<?php

// database/migrations/2024_01_01_000004_create_user_questions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained('user_locations')->onDelete('cascade');
            $table->text('question'); // نص السؤال
            $table->decimal('price', 10, 2); // سعر السؤال
            $table->boolean('is_active')->default(true); // السؤال مفعل أو لا
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('location_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_questions');
    }
};