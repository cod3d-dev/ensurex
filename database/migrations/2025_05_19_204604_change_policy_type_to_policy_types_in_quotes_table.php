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
        Schema::table('quotes', function (Blueprint $table) {
            // Remove the existing policy_type column
            $table->dropColumn('policy_type');
            
            // Add the new policy_types column as JSON
            $table->json('policy_types')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Remove the new policy_types column
            $table->dropColumn('policy_types');
            
            // Add back the original policy_type column
            $table->string('policy_type')->nullable()->after('status');
        });
    }
};
