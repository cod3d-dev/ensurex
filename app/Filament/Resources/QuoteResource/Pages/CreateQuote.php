<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Enums\PolicyType;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        // Unset age
        unset($data['age']);
        unset($data['contact']);
        unset($data['gender']);
        unset($data['is_pregnant']);
        unset($data['is_tobacco_user']);
        unset($data['is_eligible_for_coverage']);
        unset($data['zip_code']);
        unset($data['county']);
        unset($data['city']);
        unset($data['state_province']);
        unset($data['income_per_hour']);
        unset($data['hours_per_week']);
        unset($data['income_per_extra_hour']);
        unset($data['extra_hours_per_week']);
        unset($data['weeks_per_year']);
        unset($data['is_self_employed']);
        unset($data['create_new_client']);
        unset($data['self_employed_yearly_income']);


        // if ($data['create_new_client'] ?? false) {
            // Create a new contact with the provided information and attach it to the quote
            // $contact = \App\Models\Contact::create([
            //     'first_name' => $data['contact_information']['first_name'],
            //     'middle_name' => $data['contact_information']['middle_name'],
            //     'last_name' => $data['contact_information']['last_name'],
            //     'second_last_name' => $data['contact_information']['second_last_name'],
            //     'date_of_birth' => $data['contact_information']['date_of_birth'],
            //     'gender' => $data['contact_information']['gender'],
            //     'phone' => $data['contact_information']['phone'],
            //     'email_address' => $data['contact_information']['email_address'],
            //     'whatsapp' => $data['contact_information']['whatsapp'] ?? null,
            //     'kommo_id' => $data['contact_information']['kommo_id'] ?? null,
            //     'is_tobacco_user' => $data['contact_information']['is_tobacco_user'] ?? false,
            //     'is_pregnant' => $data['contact_information']['is_pregnant'] ?? false,
            //     'is_eligible_for_coverage' => $data['contact_information']['is_eligible_for_coverage'] ?? false,
            //     'zip_code' => $data['contact_information']['zip_code'],
            //     'county' => $data['contact_information']['county'],
            //     'city' => $data['contact_information']['city'],
            //     'state_province' => $data['contact_information']['state'],
            //     'created_by' => auth()->user()->id
            // ]);
            // $contact->save();
        // } else {
        //     // Update existing contact with new information
        //     $contact = \App\Models\Contact::find($data['contact_id']);
        //     if ($contact) {
        //         $contact->update([
        //             'first_name' => $data['contact_information']['first_name'],
        //             'middle_name' => $data['contact_information']['middle_name'],
        //             'last_name' => $data['contact_information']['last_name'],
        //             'second_last_name' => $data['contact_information']['second_last_name'],
        //             'date_of_birth' => $data['contact_information']['date_of_birth'],
        //             'gender' => $data['contact_information']['gender'],
        //             'phone' => $data['contact_information']['phone'],
        //             'email_address' => $data['contact_information']['email_address'],
        //             'whatsapp' => $data['contact_information']['whatsapp'] ?? null,
        //             'kommo_id' => $data['contact_information']['kommo_id'] ?? null,
        //             'is_tobacco_user' => $data['contact_information']['is_tobacco_user'] ?? false,
        //             'is_pregnant' => $data['contact_information']['is_pregnant'] ?? false,
        //             'is_eligible_for_coverage' => $data['contact_information']['is_eligible_for_coverage'] ?? false,
        //             'zip_code' => $data['contact_information']['zip_code'],
        //             'county' => $data['contact_information']['county'],
        //             'city' => $data['contact_information']['city'],
        //             'state_province' => $data['contact_information']['state'],
        //         ]);
        //     }

        // }

        // Ensure we have a contact_id for the quote

        // $data['contact_id'] = $contact->id;
        // $data['state_province'] = $data['contact_information']['state'];



        // Set main applicant data from contact information
        // $data['main_applicant'] = array_merge($data['main_applicant'] ?? [], [
        //     'relationship' => 'Aplicante Principal',
        //     'gender' => $data['contact_information']['gender'] ?? null,
        //     'is_pregnant' => $data['contact_information']['is_pregnant'] ?? false,
        //     'is_tobacco_user' => $data['contact_information']['is_tobacco_user'] ?? false,
        //     'is_eligible_for_coverage' => $data['contact_information']['is_eligible_for_coverage'] ?? false,
        // ]);

        // Calculate yearly income for main applicant if not self-employed
        // if (!($data['main_applicant']['is_self_employed'] ?? false)) {
        //     $data['main_applicant']['yearly_income'] = ($data['main_applicant']['income_per_hour'] * $data['main_applicant']['hours_per_week']
        //         + $data['main_applicant']['income_per_extra_hour'] * $data['main_applicant']['extra_hours_per_week']) * $data['main_applicant']['weeks_per_year'] ?? null;
        // }

        // Remove the create_new_client flag as it's not part of the Quote model
        // unset($data['create_new_client']);
//        unset($data['contact_information']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        $record = $this->record;
        
        // Check if contact data exists in the form data
        if (isset($data['contact'])) {
            // If the quote has a contact_id, update the contact
            if ($record->contact_id) {
                $contact = \App\Models\Contact::find($record->contact_id);
                if ($contact) {
                    try {
                        $contact->update([
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
                            'updated_by' => auth()->user()->id
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Contacto actualizado')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al actualizar contacto')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
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
                        'created_by' => auth()->user()->id
                    ]);
                    
                    // Update the quote with the new contact_id
                    $record->update(['contact_id' => $contact->id]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Contacto creado')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Error al crear contacto')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            }
        }
    }
    


    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
