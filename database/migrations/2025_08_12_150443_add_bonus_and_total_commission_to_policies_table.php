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
            if (! Schema::hasColumn('policies', 'bonus')) {
                $table->decimal('bonus', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('policies', 'total_commission')) {
                $table->decimal('total_commission', 10, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            if (Schema::hasColumn('policies', 'bonus')) {
                $table->dropColumn('bonus');
            }
            if (Schema::hasColumn('policies', 'total_commission')) {
                $table->dropColumn('total_commission');
            }
        });
    }
};
