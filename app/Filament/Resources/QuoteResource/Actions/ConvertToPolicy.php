<?php

namespace App\Filament\Resources\QuoteResource\Actions;

use App\Enums\DocumentStatus;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\QuoteStatus;
use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ConvertToPolicy extends Action
{
    protected ?Policy $policy = null;

    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('Convert to Policy')
            ->icon('heroicon-o-document-duplicate')
            ->color('success')
            ->action(function (ConvertToPolicy $action, Model $record, PolicyType $policyType): void {
                // Create new policy from the quote
                // dd($record);
                $action->policy = Policy::create([
                    'contact_id' => $record->contact_id,
                    'user_id' => auth()->id(),
                    'insurance_company_id' => $record->insurance_company_id,
                    'policy_type' => $policyType,
                    'agent_id' => $record->agent_id,
                    'quote_id' => $record->id,
                    'policy_total_cost' => $record->premium_amount,
                    'premium_amount' => $record->premium_amount,
                    'coverage_amount' => $record->coverage_amount,
                    'main_applicant' => $record->main_applicant,
                    'additional_applicants' => $record->additional_applicants,
                    'total_family_members' => $record->total_family_members,
                    'total_applicants' => $record->total_applicants,
                    'estimated_household_income' => $record->estimated_household_income,
                    'preferred_doctor' => $record->preferred_doctor,
                    'prescription_drugs' => $record->prescription_drugs,
                    'contact_information' => $record->contact_information,
                    'status' => PolicyStatus::Draft,
                    'document_status' => DocumentStatus::ToAdd,
                    'policy_year' => $record->year,
                ]);

                // Update the quote status to Converted and add policy reference
                $record->update([
                    'status' => QuoteStatus::Converted->value,
                    'policy_id' => $action->policy->id,
                ]);
            })
            ->after(function (ConvertToPolicy $action): void {
                $action->success();
                $action->redirect(PolicyResource::getUrl('edit', ['record' => $action->policy]));
            })
            ->requiresConfirmation()
            ->modalHeading('Crear Poliza')
            ->modalDescription('Se creara una poliza a partir de esta cotización.')
            ->modalSubmitActionLabel('Crear y Editar');
    }
}
