<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Notifications\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // \Log::info('HealthSherpa Form Data Before Save:', $data);

        // // Update main applicant data from contact information
        // if (isset($data['contact_information']['date_of_birth'])) {
        //     $data['main_applicant']['age'] = Carbon::parse($data['contact_information']['date_of_birth'])->age;
        // }

        // $data['main_applicant']['relationship'] = 'Aplicante Principal';
        // $data['main_applicant']['gender'] = $data['contact_information']['gender'] ?? null;
        // $data['main_applicant']['is_pregnant'] = $data['contact_information']['is_pregnant'] ?? false;
        // $data['main_applicant']['is_tobacco_user'] = $data['contact_information']['is_tobacco_user'] ?? false;
        // $data['main_applicant']['is_eligible_for_coverage'] = $data['contact_information']['is_eligible_for_coverage'] ?? false;

        // // Calculate yearly income for main applicant
        // if (!($data['main_applicant']['is_self_employed'] ?? false)) {
        //     $data['main_applicant']['yearly_income'] = ($data['main_applicant']['income_per_hour'] * $data['main_applicant']['hours_per_week']
        //         + $data['main_applicant']['income_per_extra_hour'] * $data['main_applicant']['extra_hours_per_week']) * $data['main_applicant']['weeks_per_year'] ?? null;
        // }

        // // Calculate yearly income for additional applicants
        // if (isset($data['additional_applicants'])) {
        //     foreach ($data['additional_applicants'] as $key => $additional_applicant) {
        //         if (!($additional_applicant['is_self_employed'] ?? false)) {
        //             $data['additional_applicants'][$key]['yearly_income'] = ($additional_applicant['income_per_hour'] * $additional_applicant['hours_per_week']
        //                 + $additional_applicant['income_per_extra_hour'] * $additional_applicant['extra_hours_per_week']) * $additional_applicant['weeks_per_year'] ?? null;
        //         }
        //     }
        // }

        // $data['state_province'] = $data['contact_information']['state'];

        // // Remove the separate applicant fields as they're not part of the Quote model

        // unset($data['create_new_client']);
        unset($data['contact']);

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $record = $this->record;

        \Log::info('Raw form data after save:', $data);
        \Log::info('Saved record data:', $record->toArray());

        // Check if contact data exists in the form data
        if (isset($data['contact'])) {
            // If the quote has a contact_id, update the contact
            if ($record->contact_id) {
                $contact = \App\Models\Contact::find($record->contact_id);
                if ($contact) {
                    try {
                        // Create update data array
                        $updateData = [
                            'full_name' => $data['contact']['full_name'] ?? $contact->full_name,
                            'date_of_birth' => $data['contact']['date_of_birth'] ?? $contact->date_of_birth,
                            'gender' => $data['contact']['gender'] ?? $contact->gender,
                            'phone' => $data['contact']['phone'] ?? $contact->phone,
                            'phone2' => $data['contact']['phone2'] ?? $contact->phone2,
                            'email_address' => $data['contact']['email_address'] ?? $contact->email_address,
                            'zip_code' => $data['contact']['zip_code'] ?? $contact->zip_code,
                            'county' => $data['contact']['county'] ?? $contact->county,
                            'city' => $data['contact']['city'] ?? $contact->city,
                            'state_province' => $data['contact']['state_province'] ?? $contact->state_province,
                            'kommo_id' => $data['contact']['kommo_id'] ?? $contact->kommo_id,
                            'updated_by' => auth()->user()->id,
                        ];

                        // Check if any fields were actually changed
                        $hasChanges = false;
                        foreach ($updateData as $field => $value) {
                            if ($field !== 'updated_by' && $value != $contact->$field) {
                                $hasChanges = true;
                                break;
                            }
                        }

                        // Update the contact
                        $contact->update($updateData);

                        // Only show notification if changes were made
                        if ($hasChanges) {
                            \Filament\Notifications\Notification::make()
                                ->title('Contacto actualizado')
                                ->success()
                                ->send();
                            \Log::info('Contact updated successfully with changes');
                        } else {
                            \Log::info('Contact update called but no changes were made');
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al actualizar contacto')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        \Log::error('Error updating contact: '.$e->getMessage());
                    }
                }
            } else {
                // If the quote doesn't have a contact_id, create a new contact
                try {
                    $contact = \App\Models\Contact::create([
                        'full_name' => $data['contact']['full_name'] ?? null,
                        'date_of_birth' => $data['contact']['date_of_birth'] ?? null,
                        'gender' => $data['contact']['gender'] ?? null,
                        'phone' => $data['contact']['phone'] ?? null,
                        'phone2' => $data['contact']['phone2'] ?? null,
                        'email_address' => $data['contact']['email_address'] ?? null,
                        'zip_code' => $data['contact']['zip_code'] ?? null,
                        'county' => $data['contact']['county'] ?? null,
                        'city' => $data['contact']['city'] ?? null,
                        'state_province' => $data['contact']['state_province'] ?? null,
                        'kommo_id' => $data['contact']['kommo_id'] ?? null,
                        'created_by' => auth()->user()->id,
                    ]);

                    // Update the quote with the new contact_id
                    $record->update(['contact_id' => $contact->id]);

                    \Filament\Notifications\Notification::make()
                        ->title('Contacto creado')
                        ->success()
                        ->send();
                    \Log::info('New contact created successfully');
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Error al crear contacto')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                    \Log::error('Error creating contact: '.$e->getMessage());
                }
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    // TODO: Implement custom action for income threshold confirmation in the future
}
