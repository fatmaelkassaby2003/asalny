<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('answerer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('asker_id')->constrained('users')->onDelete('cascade');
            $table->integer('additional_minutes'); // الوقت الإضافي المطلوب
            $table->text('reason')->nullable(); // سبب الطلب
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_requests');
    }

};
