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
            // Drop the foreign key constraint first
            $table->dropForeign(['user_id']);
            
            // Rename the column
            $table->renameColumn('user_id', 'asistant_id');
            
            // Add the new foreign key constraint
            $table->foreign('asistant_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_statements', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['asistant_id']);
            
            // Rename the column back
            $table->renameColumn('asistant_id', 'user_id');
            
            // Add back the original foreign key constraint
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
