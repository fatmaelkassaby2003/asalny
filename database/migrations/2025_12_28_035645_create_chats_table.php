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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asker_id');
            $table->unsignedBigInteger('answerer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('asker_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('answerer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            // Indexes
            $table->index('asker_id');
            $table->index('answerer_id');
            $table->index('order_id');
            $table->index('last_message_at');

            // Unique constraint: one chat per asker-answerer-order combination
            $table->unique(['asker_id', 'answerer_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
