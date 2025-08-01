<?php

namespace Database\Factories;

use App\Enums\IssueStatus;
use App\Models\IssueType;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueStatus = $this->faker->randomElement(IssueStatus::cases());

        if ($issueStatus == IssueStatus::ToSend || $issueStatus == IssueStatus::ToReview || $issueStatus == IssueStatus::Processing || $issueStatus == IssueStatus::Sent) {
            $verificationDate = $this->faker->dateTimeBetween('-1 day', '+1 month');
        } else {
            $verificationDate = null;
        }

        // Get a random issue type ID
        $issueTypeId = $this->faker->randomElement(IssueType::pluck('id')->toArray());
        
        // Generate description based on issue type
        $description = $this->generateDescriptionByIssueType($issueTypeId);
        
        return [
            'policy_id' => $this->faker->randomElement(Policy::pluck('id')->toArray()),
            'created_by' => $this->faker->randomElement(User::pluck('id')->toArray()),
            'issue_type_id' => $issueTypeId,
            'status' => $issueStatus,
            'verification_date' => $verificationDate,
            'email_message' => $this->faker->sentence(),
            'notes' => $this->faker->sentence(),
            'description' => $description,
            'proposed_solution' => $this->faker->sentence(),
            'response' => $this->faker->sentence(),
            'updated_by' => $this->faker->randomElement(User::pluck('id')->toArray()),
        ];
    }
    
    /**
     * Generate a realistic description based on the issue type
     *
     * @param int $issueTypeId The ID of the issue type
     * @return string A realistic description in Spanish
     */
    private function generateDescriptionByIssueType(int $issueTypeId): string
    {
        // Cargo Adicional (ID: 1)
        $cargoAdicionalPhrases = [
            'Le hicieron un cobro adicional que no corresponde a mi plan.',
            'Me cobraron más de lo que indica mi póliza.',
            'Aparece un cargo extra en mi factura que no reconozco.',
            'La aseguradora realizó un cobro no autorizado.',
            'Estoy pagando más de lo acordado en el contrato.'
        ];
        
        // Falta Cobertura (ID: 2)
        $faltaCoberturaPhases = [
            'No lo aceptaron en la clínica a pesar de tener cobertura.',
            'No le quisieron prestar el servicio médico con mi seguro.',
            'Me negaron la atención diciendo que mi póliza no cubre este servicio.',
            'El hospital rechazó mi seguro aunque debería estar cubierto.',
            'No pude usar mi seguro para el tratamiento que necesitaba.'
        ];
        
        // Default phrases if we get an unknown issue type
        $defaultPhrases = [
            'Tengo un problema con mi póliza de seguro.',
            'Necesito ayuda con un inconveniente en mi seguro.',
            'Hay un error con mi cobertura.',
            'Mi seguro no está funcionando como debería.',
            'Estoy teniendo dificultades con mi póliza.'
        ];
        
        // Select the appropriate array based on issue type ID
        switch ($issueTypeId) {
            case 1: // Cargo Adicional
                $phrases = $cargoAdicionalPhrases;
                break;
            case 2: // Falta Cobertura
                $phrases = $faltaCoberturaPhases;
                break;
            default:
                $phrases = $defaultPhrases;
        }
        
        // Return a random phrase from the selected array
        return $this->faker->randomElement($phrases);
    }
}
