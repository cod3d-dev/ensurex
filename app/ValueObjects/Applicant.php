<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Carbon\Carbon;

class Applicant implements Arrayable
{
    public function __construct(
        public string $gender,
        public string $date_of_birth,
        public ?string $relationship = null,
        public ?string $first_name = null,
        public ?string $middle_name = null,
        public ?string $last_name = null,
        public ?string $second_last_name = null,
        public ?string $fullname = null,
        public bool $is_tobacco_user = false,
        public bool $is_pregnant = false,
        public bool $is_eligible_for_coverage = false,
        public bool $medicaid_client = false,
        public ?string $country_of_birth = null,
        public ?string $civil_status = null,
        public ?string $phone1 = null,
        public ?string $email_address = null,
        public ?string $height = null,
        public ?string $weight = null,
        public ?string $preferred_doctor = null,
        public array $prescription_drugs = [],
        public ?string $member_ssn = null,
        public ?string $member_ssn_date = null,
        public ?string $member_passport = null,
        public ?string $member_green_card = null,
        public ?string $member_green_card_expedition_date = null,
        public ?string $member_green_card_expiration_date = null,
        public ?string $member_work_permit = null,
        public ?string $member_work_permit_expedition_date = null,
        public ?string $member_work_permit_expiration_date = null,
        public ?string $member_driver_license = null,
        public ?string $member_driver_license_expedition_date = null,
        public ?string $member_uscis = null,
        public ?string $member_inmigration_status = null,
        public ?string $member_inmigration_status_category = null,
        public ?string $employer_1_name = null,
        public ?string $employer_1_role = null,
        public ?string $employer_1_phone = null,
        public ?string $employer_1_income = null,
        public ?string $employer_2_name = null,
        public ?string $employer_2_role = null,
        public ?string $employer_2_phone = null,
        public ?string $employer_2_income = null,
        public ?string $employer_3_name = null,
        public ?string $employer_3_role = null,
        public ?string $employer_3_phone = null,
        public ?string $employer_3_income = null,
        public ?float $yearly_income = null,
        public bool $is_self_employed = false,
        public ?string $self_employed_profession = null,
        public ?float $income_per_hour = null,
        public ?int $hours_per_week = null,
        public ?float $income_per_extra_hour = null,
        public ?int $extra_hours_per_week = null,
        public ?int $weeks_per_year = null,
        public ?float $self_employed_yearly_income = null,
        public ?int $age = null,
    ) {


        // If we have a date of birth but no age, calculate the age
        if ($this->date_of_birth && !$this->age) {
            $this->age = Carbon::parse($this->date_of_birth)->age;
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            gender: self::normalizeGender($data['gender'] ?? ''),
            date_of_birth: $data['date_of_birth'] ?? '',
            relationship: $data['relationship'] ?? null,
            first_name: $data['first_name'] ?? null,
            middle_name: $data['middle_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            second_last_name: $data['second_last_name'] ?? null,
            fullname: $data['fullname'] ?? null,
            is_tobacco_user: $data['is_tobacco_user'] ?? false,
            is_pregnant: $data['is_pregnant'] ?? false,
            is_eligible_for_coverage: $data['is_eligible_for_coverage'] ?? false,
            medicaid_client: $data['medicaid_client'] ?? false,
            country_of_birth: $data['country_of_birth'] ?? null,
            civil_status: $data['civil_status'] ?? null,
            phone1: $data['phone1'] ?? null,
            email_address: $data['email_address'] ?? null,
            height: $data['height'] ?? null,
            weight: $data['weight'] ?? null,
            preferred_doctor: $data['preferred_doctor'] ?? null,
            prescription_drugs: $data['prescription_drugs'] ?? [],
            member_ssn: $data['member_ssn'] ?? null,
            member_ssn_date: $data['member_ssn_date'] ?? null,
            member_passport: $data['member_passport'] ?? null,
            member_green_card: $data['member_green_card'] ?? null,
            member_green_card_expedition_date: $data['member_green_card_expedition_date'] ?? null,
            member_green_card_expiration_date: $data['member_green_card_expiration_date'] ?? null,
            member_work_permit: $data['member_work_permit'] ?? null,
            member_work_permit_expedition_date: $data['member_work_permit_expedition_date'] ?? null,
            member_work_permit_expiration_date: $data['member_work_permit_expiration_date'] ?? null,
            member_driver_license: $data['member_driver_license'] ?? null,
            member_driver_license_expedition_date: $data['member_driver_license_expedition_date'] ?? null,
            member_uscis: $data['member_uscis'] ?? null,
            member_inmigration_status: $data['member_inmigration_status'] ?? null,
            member_inmigration_status_category: $data['member_inmigration_status_category'] ?? null,
            employer_1_name: $data['employer_1_name'] ?? null,
            employer_1_role: $data['employer_1_role'] ?? null,
            employer_1_phone: $data['employer_1_phone'] ?? null,
            employer_1_income: $data['employer_1_income'] ?? null,
            employer_2_name: $data['employer_2_name'] ?? null,
            employer_2_role: $data['employer_2_role'] ?? null,
            employer_2_phone: $data['employer_2_phone'] ?? null,
            employer_2_income: $data['employer_2_income'] ?? null,
            employer_3_name: $data['employer_3_name'] ?? null,
            employer_3_role: $data['employer_3_role'] ?? null,
            employer_3_phone: $data['employer_3_phone'] ?? null,
            employer_3_income: $data['employer_3_income'] ?? null,
            yearly_income: $data['yearly_income'] ?? null,
            is_self_employed: $data['is_self_employed'] ?? false,
            self_employed_profession: $data['self_employed_profession'] ?? null,
            income_per_hour: $data['income_per_hour'] ?? null,
            hours_per_week: $data['hours_per_week'] ?? null,
            income_per_extra_hour: $data['income_per_extra_hour'] ?? null,
            extra_hours_per_week: $data['extra_hours_per_week'] ?? null,
            weeks_per_year: $data['weeks_per_year'] ?? null,
            self_employed_yearly_income: $data['self_employed_yearly_income'] ?? null,
            age: $data['age'] ?? null,
        );
    }

    private static function normalizeGender(?string $gender): string
    {
        return match (strtoupper($gender)) {
            'F', 'FEMALE' => 'female',
            'M', 'MALE' => 'male',
            default => 'other'
        };
    }

    public static function requiredFieldsForQuote(): array
    {
        return [
            'gender',
            'date_of_birth',
        ];
    }

    public static function requiredFieldsForPolicy(): array
    {
        return [
            ...self::requiredFieldsForQuote(),
            'first_name',
            'last_name',
            'phone1',
        ];
    }

    public function isCompleteForQuote(): bool
    {
        return collect(self::requiredFieldsForQuote())
            ->every(fn ($field) => !is_null($this->{$field}));
    }

    public function isCompleteForPolicy(): bool
    {
        return collect(self::requiredFieldsForPolicy())
            ->every(fn ($field) => !is_null($this->{$field}));
    }

    public function isMedicaidClient(): bool
    {
        return $this->medicaid_client;
    }

    public function getMissingPolicyFields(): array
    {
        return collect(self::requiredFieldsForPolicy())
            ->filter(fn ($field) => is_null($this->{$field}))
            ->values()
            ->all();
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return Carbon::parse($this->date_of_birth)->age;
    }

    public function toArray(): array
    {
        return [
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'relationship' => $this->relationship,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'second_last_name' => $this->second_last_name,
            'fullname' => $this->fullname,
            'is_tobacco_user' => $this->is_tobacco_user,
            'is_pregnant' => $this->is_pregnant,
            'is_eligible_for_coverage' => $this->is_eligible_for_coverage,
            'medicaid_client' => $this->medicaid_client,
            'country_of_birth' => $this->country_of_birth,
            'civil_status' => $this->civil_status,
            'phone1' => $this->phone1,
            'email_address' => $this->email_address,
            'height' => $this->height,
            'weight' => $this->weight,
            'preferred_doctor' => $this->preferred_doctor,
            'prescription_drugs' => $this->prescription_drugs,
            'member_ssn' => $this->member_ssn,
            'member_ssn_date' => $this->member_ssn_date,
            'member_passport' => $this->member_passport,
            'member_green_card' => $this->member_green_card,
            'member_green_card_expedition_date' => $this->member_green_card_expedition_date,
            'member_green_card_expiration_date' => $this->member_green_card_expiration_date,
            'member_work_permit' => $this->member_work_permit,
            'member_work_permit_expedition_date' => $this->member_work_permit_expedition_date,
            'member_work_permit_expiration_date' => $this->member_work_permit_expiration_date,
            'member_driver_license' => $this->member_driver_license,
            'member_driver_license_expedition_date' => $this->member_driver_license_expedition_date,
            'member_uscis' => $this->member_uscis,
            'member_inmigration_status' => $this->member_inmigration_status,
            'member_inmigration_status_category' => $this->member_inmigration_status_category,
            'employer_1_name' => $this->employer_1_name,
            'employer_1_role' => $this->employer_1_role,
            'employer_1_phone' => $this->employer_1_phone,
            'employer_1_income' => $this->employer_1_income,
            'employer_2_name' => $this->employer_2_name,
            'employer_2_role' => $this->employer_2_role,
            'employer_2_phone' => $this->employer_2_phone,
            'employer_2_income' => $this->employer_2_income,
            'employer_3_name' => $this->employer_3_name,
            'employer_3_role' => $this->employer_3_role,
            'employer_3_phone' => $this->employer_3_phone,
            'employer_3_income' => $this->employer_3_income,
            'yearly_income' => $this->yearly_income,
            'is_self_employed' => $this->is_self_employed,
            'self_employed_profession' => $this->self_employed_profession,
            'income_per_hour' => $this->income_per_hour,
            'hours_per_week' => $this->hours_per_week,
            'income_per_extra_hour' => $this->income_per_extra_hour,
            'extra_hours_per_week' => $this->extra_hours_per_week,
            'weeks_per_year' => $this->weeks_per_year,
            'self_employed_yearly_income' => $this->self_employed_yearly_income,
            'age' => $this->age,
        ];
    }
}
