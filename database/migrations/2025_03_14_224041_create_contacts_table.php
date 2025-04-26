<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // System Fields
            $table->boolean('is_lead')->default(true);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->datetime('last_contact_date')->nullable();
            $table->datetime('next_follow_up_date')->nullable();
            $table->enum('preferred_contact_method', ['email', 'phone', 'sms'])->nullable();
            $table->time('preferred_contact_time')->nullable();
            $table->string('referral_source')->nullable();
            $table->boolean('is_eligible_for_coverage')->default(false);
            $table->text('notes')->nullable();

            // Personal Information
            $table->enum('preferred_language', ['spanish', 'english'])->default('spanish');
            $table->string('code')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('second_last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('email_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone2')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('kommo_id')->nullable();
            $table->string('kynect_case_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('country_of_birth')->nullable();
            $table->decimal('weight', 5, 2)->nullable(); // Up to 999.99
            $table->decimal('height', 5, 2)->nullable(); // Up to 99.99
            $table->boolean('is_tobacco_user')->default(false);
            $table->boolean('is_pregnant')->default(false);
            $table->boolean('has_made_felony')->default(false);
            $table->boolean('has_declared_bankruptcy')->default(false);
            $table->boolean('license_has_been_revoked')->default(false);


            // Employment Information
            // Source 1
            $table->string('employer_name_1')->nullable();
            $table->string('employer_phone_1')->nullable();
            $table->string('position_1')->nullable();
            $table->decimal('annual_income_1', 12, 2)->nullable();
            // Source 2
            $table->string('employer_name_2')->nullable();
            $table->string('employer_phone_2')->nullable();
            $table->string('position_2')->nullable();
            $table->decimal('annual_income_2', 12, 2)->nullable();
            // Source 3
            $table->string('employer_name_3')->nullable();
            $table->string('employer_phone_3')->nullable();
            $table->string('position_3')->nullable();
            $table->decimal('annual_income_3', 12, 2)->nullable();

            // Immigration/Legal Documents
            $table->string('immigration_status')->nullable();
            $table->string('immigration_status_category')->nullable();
            $table->string('passport_number')->nullable();
            $table->string('alien_number')->nullable();
            $table->string('uscis_number')->nullable();
            $table->string('aptc_number')->nullable();
            $table->string('ssn')->nullable();
            $table->date('ssn_issue_date')->nullable();
            $table->string('green_card_number')->nullable();
            $table->date('green_card_expiration_date')->nullable();
            $table->string('work_permit_number')->nullable();
            $table->date('work_permit_emissions_date')->nullable();
            $table->date('work_permit_expiration_date')->nullable();
            $table->string('driver_license_number')->nullable();
            $table->date('driver_license_emission_date')->nullable();
            $table->string('driver_license_emissions_state')->nullable();


            // Physical Address
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('county')->nullable();

            // Mailing Address
            $table->boolean('is_same_as_physical')->default(true);
            $table->string('mailing_address_line_1')->nullable();
            $table->string('mailing_address_line_2')->nullable();
            $table->string('mailing_city')->nullable();
            $table->string('mailing_state_province')->nullable();
            $table->string('mailing_zip_code')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
