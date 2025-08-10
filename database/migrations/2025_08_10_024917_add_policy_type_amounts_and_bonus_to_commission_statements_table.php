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
        Schema::table('commission_statements', function (Blueprint $table) {
            $table->decimal('health_policy_amount', 10, 2)->nullable()->default(0);
            $table->decimal('accident_policy_amount', 10, 2)->nullable()->default(0);
            $table->decimal('vision_policy_amount', 10, 2)->nullable()->default(0);
            $table->decimal('dental_policy_amount', 10, 2)->nullable()->default(0);
            $table->decimal('life_policy_amount', 10, 2)->nullable()->default(0);
            $table->decimal('bonus_amount', 10, 2)->nullable()->default(0);
            $table->text('bonus_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_statements', function (Blueprint $table) {
            $table->dropColumn([
                'health_policy_amount',
                'accident_policy_amount',
                'vision_policy_amount',
                'dental_policy_amount',
                'life_policy_amount',
                'bonus_amount',
                'bonus_notes',
            ]);
        });
    }
};
