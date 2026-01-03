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
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('dispute_count')->default(0)->after('payment_status');
            $table->text('dispute_reason')->nullable()->after('dispute_count');
            $table->timestamp('approved_at')->nullable()->after('paid_at');
            $table->timestamp('disputed_at')->nullable()->after('approved_at');
            $table->timestamp('escalated_at')->nullable()->after('disputed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'dispute_count',
                'dispute_reason',
                'approved_at',
                'disputed_at',
                'escalated_at',
            ]);
        });
    }
};
