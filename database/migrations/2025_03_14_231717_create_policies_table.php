<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PolicyStatus;
use App\Enums\DocumentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->index()->unique()->nullable();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('insurance_company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('policy_type')->nullable();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained()->nullOnDelete();
            $table->string('policy_number')->nullable();
            $table->integer('policy_year')->nullable();
            $table->boolean('has_existing_kynect_case')->default(false);
            $table->string('policy_us_county')->nullable();
            $table->string('policy_us_state')->nullable();
            $table->string('kynect_case_number')->nullable(); // Case number in Kynect
            $table->string('insurance_company_policy_number')->nullable(); // Policy number in insurance company system
            $table->string('policy_plan')->nullable();
            $table->string('policy_level')->nullable();
            $table->decimal('policy_total_cost', 10, 2)->nullable();
            $table->decimal('policy_total_subsidy', 10, 2)->nullable();
            $table->decimal('premium_amount', 10, 2)->nullable();
            $table->decimal('coverage_amount', 12, 2)->nullable();
            $table->boolean('recurring_payment')->default(false)->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->date('last_payment_date')->nullable();
            $table->integer('preferred_payment_day')->nullable();
            $table->boolean('initial_paid')->default(false)->nullable();
            $table->boolean('autopay')->default(false)->nullable();
            $table->boolean('requires_aca')->default(false);
            $table->boolean('aca')->default(false)->nullable();
            $table->string('document_status')->nullable();
            $table->date('next_document_expiration_date')->nullable();
            $table->text('observations')->nullable();
            $table->boolean('client_notified')->default(false);
            $table->boolean('life_offered')->default(false);

            // Family Information
            $table->json('main_applicant')->nullable();
            $table->json('additional_applicants')->nullable();
            $table->integer('total_family_members')->default(1)->nullable();
            $table->integer('total_applicants')->default(1)->nullable();
            $table->integer('total_applicants_with_medicaid')->default(0)->nullable();

            // Additional Information
            $table->decimal('estimated_household_income', 12, 2)->nullable();
            $table->string('preferred_doctor')->nullable();
            $table->text('prescription_drugs')->nullable();
            $table->json('contact_information')->nullable();
            $table->json('life_insurance')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            
            // Policy Status and Dates
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('previous_year_policy_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('status_changed_date')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_initial_verification_complete')->default(false);
            $table->dateTime('initial_verification_date')->nullable();
            $table->foreignId('initial_verification_performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();

            // Payment Information
            $table->string('payment_card_type')->nullable();
            $table->string('payment_card_bank')->nullable();
            $table->text('payment_card_holder')->nullable();
            $table->text('payment_card_number')->nullable();
            $table->text('payment_card_exp_month')->nullable();
            $table->text('payment_card_exp_year')->nullable();
            $table->text('payment_card_cvv')->nullable();

            // Bank Account Information
            $table->string('payment_bank_account_bank')->nullable();
            $table->text('payment_bank_account_holder')->nullable();
            $table->text('payment_bank_account_aba')->nullable();
            $table->text('payment_bank_account_number')->nullable();

            // Billing Address
            $table->text('billing_address_1')->nullable();
            $table->text('billing_address_2')->nullable();
            $table->string('billing_address_city')->nullable();
            $table->string('billing_address_state')->nullable();
            $table->string('billing_address_zip')->nullable();

            // Renewal fields
            $table->boolean('is_renewal')->default(false);
            $table->foreignId('renewed_from_policy_id')->nullable()->constrained('policies')->nullOnDelete();
            $table->foreignId('renewed_to_policy_id')->nullable()->constrained('policies')->nullOnDelete();
            $table->foreignId('renewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('renewed_at')->nullable();
            $table->string('renewal_status')->nullable(); // pending, completed, cancelled
            $table->text('renewal_notes')->nullable();

            // Audit Information
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policies');
    }
};
