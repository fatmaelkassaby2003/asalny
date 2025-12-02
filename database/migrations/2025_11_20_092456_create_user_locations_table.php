<?php

// database/migrations/2024_01_01_000003_create_user_locations_table.php

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
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); // اسم المكان (مثل: المنزل، العمل)
            $table->decimal('latitude', 10, 7); // خط العرض
            $table->decimal('longitude', 10, 7); // خط الطول
            $table->string('address')->nullable(); // العنوان الكامل
            $table->boolean('is_default')->default(false); // الموقع الافتراضي
            $table->timestamps();
            
            // Index للبحث السريع
            $table->index('user_id');
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};