<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('kynect_fpl', function (Blueprint $table) {
            $table->id();
            $table->year('year')->unique();
            // Monthly income for each household size
            $table->decimal('members_1', 10, 2);
            $table->decimal('members_2', 10, 2);
            $table->decimal('members_3', 10, 2);
            $table->decimal('members_4', 10, 2);
            $table->decimal('members_5', 10, 2);
            $table->decimal('members_6', 10, 2);
            $table->decimal('members_7', 10, 2);
            $table->decimal('members_8', 10, 2);
            $table->decimal('additional_member', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kynect_fpl');
    }
};
