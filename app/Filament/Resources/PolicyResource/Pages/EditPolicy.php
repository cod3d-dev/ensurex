<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Poliza';
    protected static ?string $navigationIcon = 'iconoir-privacy-policy';

    public static string|\Filament\Support\Enums\Alignment $formActionsAlignment = 'end';


    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if(!isset($data['main_applicant'])) {
            $data['main_applicant'] = [];
        }

        if(!isset($data['contact_information'])) {
            $data['contact_information'] = [];
        }

        // Get the contact relation through the record
        $contact = $this->record->contact;
        
        $data['contact_information']['first_name'] = $contact->first_name ?? null;
        $data['contact_information']['middle_name'] = $contact->middle_name ?? null;
        $data['contact_information']['last_name'] = $contact->last_name ?? null;
        $data['contact_information']['second_last_name'] = $contact->second_last_name ?? null;

        $data['main_applicant']['fullname'] = $data['contact_information']['first_name'] . ' ' . $data['contact_information']['middle_name'] . $data['contact_information']['last_name'] . $data['contact_information']['second_last_name'];

        if ($data['policy_us_state'] === 'KY' ) {
            $data['requires_aca'] = true;
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $policy = $this->record;
        
        // Check if the contact_id has changed
        if ($policy->isDirty('contact_id') || $policy->wasChanged('contact_id')) {
            $contactId = $policy->contact_id;
            
            // Find the existing 'self' applicant
            $selfApplicant = $policy->policyApplicants()
                ->where('relationship_with_policy_owner', 'self')
                ->first();
            
            if ($selfApplicant) {
                // Update the existing 'self' applicant with the new contact_id
                $selfApplicant->update(['contact_id' => $contactId]);
            } else {
                // Create a new 'self' applicant if none exists
                $policy->policyApplicants()->create([
                    'contact_id' => $contactId,
                    'relationship_with_policy_owner' => 'self',
                    'is_covered_by_policy' => true,
                ]);
            }
        }
        
        // Mark this page as completed
        $policy->markPageCompleted('edit_policy');
    }
}
