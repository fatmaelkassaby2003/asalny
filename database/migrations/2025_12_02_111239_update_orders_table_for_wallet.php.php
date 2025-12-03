<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('held_amount', 10, 2)->default(0)->after('price');
            $table->boolean('amount_transferred')->default(false)->after('held_amount');
            $table->timestamp('amount_transferred_at')->nullable()->after('amount_transferred');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['held_amount', 'amount_transferred', 'amount_transferred_at']);
        });
    }
};
