<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\DocumentType;
use App\Models\Policy;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PolicyDocument>
 */
class PolicyDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'policy_id' => $this->faker->randomElement(Policy::pluck('id')->toArray()),
            'user_id' => $this->faker->randomElement(User::pluck('id')->toArray()),
            'document_type_id' => $this->faker->randomElement(DocumentType::pluck('id')->toArray()),
            'status' => $this->faker->randomElement(DocumentStatus::cases()),
            'sent_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'status_updated_by' => $this->faker->randomElement(User::pluck('id')->toArray()),
            'status_updated_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'due_date' => $this->faker->dateTimeBetween('-1 week', '+2 months'),
            'name' => function (array $attributes) {
                // Get the policy and its applicants
                $policy = Policy::find($attributes['policy_id']);

                if ($policy) {
                    // Get document type name
                    $documentType = DocumentType::find($attributes['document_type_id']);
                    $documentTypeName = $documentType ? $documentType->name : 'Documento';

                    // Get a random applicant from the policy
                    $applicant = $policy->policyApplicants()->inRandomOrder()->first();

                    if ($applicant && $applicant->contact) {
                        // Map common document types to Spanish
                        $spanishDocTypes = [
                            'Income' => 'Ingresos de',
                            'SSN' => 'Social de',
                            'Address' => 'Dirección de',
                            'APTC' => 'APTC de',
                            'Citizenship' => 'Citizenship de',
                            'EAD' => 'EAD de',
                            'Household' => 'Household de',
                            'Immigration Exp' => 'Immigration Expiration de',
                            'Driver License' => 'Licencia de Conducir de',
                            'Lawful' => 'Lawful Presence de',
                            'Loss Employment' => 'Loss of Employment de',
                            'Passport' => 'Pasaporte de',
                            'Residency' => 'Residency de',
                            'Social' => 'Social de',
                            'Status' => 'Status de',
                        ];

                        // Get Spanish prefix or default to document type name
                        $prefix = $spanishDocTypes[$documentTypeName] ?? $documentTypeName.' de';

                        // Return the Spanish name format
                        return $prefix.' '.$applicant->contact->full_name;
                    }
                }

                // Fallback if relationships can't be resolved
                return 'Documento '.$this->faker->word();
            },
            'notes' => $this->faker->sentence(),
        ];
    }

    public function expiresNextWeek(): static
    {
        // Get all statuses except Approved
        $statuses = array_filter(DocumentStatus::cases(), function ($status) {
            return $status !== DocumentStatus::Approved;
        });

        return $this->state([
            'due_date' => Carbon::now()->addWeeks(1),
            'status' => $this->faker->randomElement($statuses),
        ]);
    }

    public function expireTomorrow(): static
    {
        // Get all statuses except Approved
        $statuses = array_filter(DocumentStatus::cases(), function ($status) {
            return $status !== DocumentStatus::Approved;
        });

        return $this->state([
            'due_date' => Carbon::now()->addDays(1),
            'status' => $this->faker->randomElement($statuses),
        ]);
    }

    public function expireYesterday(): static
    {
        // Get all statuses except Approved
        $statuses = array_filter(DocumentStatus::cases(), function ($status) {
            return $status !== DocumentStatus::Approved;
        });

        return $this->state([
            'due_date' => Carbon::now()->subDays(1),
            'status' => $this->faker->randomElement($statuses),
        ]);
    }

    public function expireToday(): static
    {
        // Get all statuses except Approved
        $statuses = array_filter(DocumentStatus::cases(), function ($status) {
            return $status !== DocumentStatus::Approved;
        });

        return $this->state([
            'due_date' => Carbon::now(),
            'status' => $this->faker->randomElement($statuses),
        ]);
    }

    public function expireLastWeek(): static
    {
        // Get all statuses except Approved
        $statuses = array_filter(DocumentStatus::cases(), function ($status) {
            return $status !== DocumentStatus::Approved;
        });

        return $this->state([
            'due_date' => Carbon::now()->subWeeks(1),
            'status' => $this->faker->randomElement($statuses),
        ]);
    }
}
