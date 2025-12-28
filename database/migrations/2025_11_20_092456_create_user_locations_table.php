<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('latitude', 10, 7); // خط العرض (مطلوب)
            $table->decimal('longitude', 10, 7); // خط الطول (مطلوب)
            $table->boolean('is_current')->default(false); // الموقع الحالي
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index(['latitude', 'longitude']);
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};