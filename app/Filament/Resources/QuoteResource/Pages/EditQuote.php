<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }



    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('HealthSherpa Form Data Before Save:', $data);

        // Update main applicant data from contact information
        if (isset($data['contact_information']['date_of_birth'])) {
            $data['main_applicant']['age'] = Carbon::parse($data['contact_information']['date_of_birth'])->age;
        }

        $data['main_applicant']['relationship'] = 'Aplicante Principal';
        $data['main_applicant']['gender'] = $data['contact_information']['gender'] ?? null;
        $data['main_applicant']['is_pregnant'] = $data['contact_information']['is_pregnant'] ?? false;
        $data['main_applicant']['is_tobacco_user'] = $data['contact_information']['is_tobacco_user'] ?? false;
        $data['main_applicant']['is_eligible_for_coverage'] = $data['contact_information']['is_eligible_for_coverage'] ?? false;

        // Calculate yearly income for main applicant
        if (!($data['main_applicant']['is_self_employed'] ?? false)) {
            $data['main_applicant']['yearly_income'] = ($data['main_applicant']['income_per_hour'] * $data['main_applicant']['hours_per_week']
                + $data['main_applicant']['income_per_extra_hour'] * $data['main_applicant']['extra_hours_per_week']) * $data['main_applicant']['weeks_per_year'] ?? null;
        }

        // Calculate yearly income for additional applicants
        if (isset($data['additional_applicants'])) {
            foreach ($data['additional_applicants'] as $key => $additional_applicant) {
                if (!($additional_applicant['is_self_employed'] ?? false)) {
                    $data['additional_applicants'][$key]['yearly_income'] = ($additional_applicant['income_per_hour'] * $additional_applicant['hours_per_week']
                        + $additional_applicant['income_per_extra_hour'] * $additional_applicant['extra_hours_per_week']) * $additional_applicant['weeks_per_year'] ?? null;
                }
            }
        }

        $data['state_province'] = $data['contact_information']['state'];

        // Remove the separate applicant fields as they're not part of the Quote model

        unset($data['create_new_client']);
//        unset($data['contact_information']);
        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $record = $this->record;

        // dd($record->main_applicant);

        \Log::info('Raw form data after save:', $data);
        \Log::info('Saved record data:', $record->toArray());

        if (isset($record->contact_id) && isset($record->contact_information)) {
            $contact = \App\Models\Contact::find($record->contact_id);
            if ($contact) {
                try {
                    $contact->update([
                        'first_name' => $record->contact_information['first_name'] ?? $contact->first_name,
                        'middle_name' => $record->contact_information['middle_name'] ?? $contact->middle_name,
                        'last_name' => $record->contact_information['last_name'] ?? $contact->last_name,
                        'second_last_name' => $record->contact_information['second_last_name'] ?? $contact->second_last_name,
                        'date_of_birth' => $record->contact_information['date_of_birth'] ?? $contact->date_of_birth,
                        'gender' => $record->contact_information['gender'] ?? $contact->gender,
                        'phone' => $record->contact_information['phone'] ?? $contact->phone,
                        'whatsapp' => $record->contact_information['whatsapp'] ?? $contact->whatsapp,
                        'email_address' => $record->contact_information['email_address'] ?? $contact->email_address,
                        'zip_code' => $record->contact_information['zip_code'] ?? $contact->zip_code,
                        'county' => $record->contact_information['county'] ?? $contact->county,
                        'city' => $record->contact_information['city'] ?? $contact->city,
                        'state_province' => $record->contact_information['state'] ?? $contact->state_province,
                        'is_tobacco_user' => $record->contact_information['is_tobacco_user'] ?? $contact->is_tobacco_user,
                        'is_pregnant' => $record->contact_information['is_pregnant'] ?? $contact->is_pregnant,
                        'is_eligible_for_coverage' => $record->contact_information['is_eligible_for_coverage'] ?? $contact->is_eligible_for_coverage,
                        'kommo_id' => $record->contact_information['kommo_id'] ?? $contact->kommo_id,
                    ]);
                    \Log::info('Contact updated successfully');
                } catch (\Exception $e) {
                    \Log::error('Error updating contact: ' . $e->getMessage());
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
