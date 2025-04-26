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
        Schema::create('policy_applicants', function (Blueprint $table) {
            $table->id();
            $table->integer('sort_order')->nullable();
            $table->foreignId('policy_id')->nullable()->constrained('policies')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete();
            $table->string('relationship_with_policy_owner')->nullable();
            $table->boolean('is_covered_by_policy')->default(true);
            $table->boolean('medicaid_client')->default(false);
            $table->string('employer_1_name')->nullable();
            $table->string('employer_1_role')->nullable();
            $table->string('employer_1_phone')->nullable();
            $table->string('employer_1_address')->nullable();
            $table->float('income_per_hour')->nullable();
            $table->integer('hours_per_week')->nullable();
            $table->float('income_per_extra_hour')->nullable();
            $table->integer('extra_hours_per_week')->nullable();
            $table->integer('weeks_per_year')->nullable();
            $table->float('yearly_income')->nullable();
            $table->boolean('is_self_employed')->default(false);
            $table->string('self_employed_profession')->nullable();
            $table->float('self_employed_yearly_income')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_applicants');
    }
};
