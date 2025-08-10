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
        Schema::table('policies', function (Blueprint $table) {
            $table->foreignId('commission_statement_id')->nullable()->constrained('commission_statements')->nullOnDelete();
            $table->date('activation_date')->after('status_changed_by')->nullable();
            $table->decimal('commission_rate_per_policy', 10, 2)->nullable();
            $table->decimal('commission_rate_per_additional_applicant', 10, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.php
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn([
                'commission_statement_id',
                'activation_date',
                'commission_rate_per_policy',
                'commission_rate_per_additional_applicant',
                'commission_amount',
            ]);
        });
    }
};
