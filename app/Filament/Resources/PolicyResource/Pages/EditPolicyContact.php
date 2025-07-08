<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Enums\MaritialStatus;
use App\Enums\UsState;
use App\Filament\Resources\PolicyResource;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPolicyContact extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Titular';

    protected static ?string $navigationIcon = 'eos-perm-contact-calendar-o';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('Datos del Titular')
                    ->schema([
                        Forms\Components\Select::make('contact_id')
                            ->label('Cliente')
                            ->disabled()
                            ->relationship('contact', 'full_name')
                            ->searchable()
                            ->live()
                            ->options(function () {
                                return Contact::all()->pluck('full_name', 'id')->toArray();
                            })
                            ->preload()
                            ->required()
                            ->suffixActions([
                                // Action to create a new contact
                                Forms\Components\Actions\Action::make('createNewContact')
                                    ->icon('heroicon-m-plus-circle')
                                    ->label('Crear nuevo contacto')
                                    ->action(function (array $data) {
                                        // Create the new contact
                                        $contact = Contact::create([
                                            'full_name' => $data['full_name'],
                                            'email_address' => $data['email_address'] ?? null,
                                            'phone' => $data['phone'] ?? null,
                                        ]);

                                        // Get the current policy record
                                        $policy = $this->record;

                                        // Update the policy with the new contact
                                        $policy->update(['contact_id' => $contact->id]);

                                        // First, check if there are any existing 'self' applicants that are not the current contact
                                        $existingSelfApplicants = $policy->policyApplicants()
                                            ->where('relationship_with_policy_owner', 'self')
                                            ->where('contact_id', '!=', $contact->id)
                                            ->get();

                                        // If there are any, change their relationship to 'other'
                                        foreach ($existingSelfApplicants as $applicant) {
                                            $applicant->update([
                                                'relationship_with_policy_owner' => 'other',
                                            ]);
                                        }

                                        // Create a new 'self' applicant for the new contact
                                        $policy->policyApplicants()->create([
                                            'contact_id' => $contact->id,
                                            'relationship_with_policy_owner' => 'self',
                                            'sort_order' => 0,
                                        ]);

                                        // Show a success notification
                                        Notification::make()
                                            ->title('Contacto creado')
                                            ->body('El nuevo contacto ha sido creado y asignado a la póliza.')
                                            ->success()
                                            ->send();

                                        // Refresh the form data to load the new contact's information
                                        $this->fillForm();
                                    })
                                    ->form([
                                        Forms\Components\TextInput::make('full_name')
                                            ->label('Nombre completo')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('email_address')
                                            ->label('Correo electrónico')
                                            ->email(),
                                        Forms\Components\TextInput::make('phone_number')
                                            ->label('Teléfono'),
                                    ])
                                    ->modalHeading('Crear nuevo contacto')
                                    ->modalDescription('Cree un nuevo contacto que será asignado como titular de la póliza.')
                                    ->modalSubmitActionLabel('Crear y asignar')
                                    ->modalCancelActionLabel('Cancelar')
                                    ->modalWidth('md'),

                                // Action to change to an existing contact
                                Forms\Components\Actions\Action::make('changeContact')
                                    ->icon('heroicon-m-user-plus')
                                    ->label('Cambiar contacto')
                                    ->requiresConfirmation()
                                    ->modalDescription('¿Está seguro que desea cambiar el contacto? Todos los datos del formulario serán actualizados con la información del nuevo contacto.')
                                    ->modalSubmitActionLabel('Sí, cambiar contacto')
                                    ->modalCancelActionLabel('Cancelar')
                                    ->action(function (Forms\Get $get, Forms\Set $set, array $data) {
                                        // Get the current policy record
                                        $policy = $this->record;
                                        $newContactId = $data['new_contact_id'];
                                        $newContact = Contact::find($newContactId);

                                        if (! $newContact) {
                                            Notification::make()
                                                ->title('Error')
                                                ->body('No se encontró el contacto seleccionado.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        // Update the contact_id directly in the database
                                        $policy->update(['contact_id' => $newContactId]);

                                        // Update the policy applicant with relationship 'self'
                                        $selfApplicant = $policy->policyApplicants()
                                            ->where('relationship_with_policy_owner', 'self')
                                            ->first();

                                        if ($selfApplicant) {
                                            // Update the existing 'self' applicant with the new contact_id
                                            $selfApplicant->update(['contact_id' => $newContactId, 'is_covered_by_policy' => $policy->contact_is_applicant]);
                                        } else {
                                            // Create a new 'self' applicant if none exists
                                            $policy->policyApplicants()->create([
                                                'contact_id' => $newContactId,
                                                'relationship_with_policy_owner' => 'self',
                                                'is_covered_by_policy' => $policy->contact_is_applicant,
                                            ]);
                                        }

                                        // Show a success notification
                                        Notification::make()
                                            ->title('Contacto actualizado')
                                            ->body('El contacto ha sido actualizado y se ha actualizado el aplicante principal.')
                                            ->success()
                                            ->send();

                                        // Refresh the form data to load the new contact's information
                                        $this->fillForm();
                                    })
                                    ->form([
                                        Forms\Components\Select::make('new_contact_id')
                                            ->label('Seleccionar nuevo contacto')
                                            ->options(function () {
                                                return Contact::all()->pluck('full_name', 'id')->toArray();
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                    ])
                                    ->modalHeading('Cambiar contacto')
                                    ->modalWidth('md'),
                            ])
                            ->columnSpan(2),
                        Forms\Components\TextInput::make('contact.code')
                            ->label('Codigo Cliente')
                            ->disabled(),
                        Forms\Components\TextInput::make('formatted_created_at')
                            ->label('Cliente Desde')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $state, $record) {
                                if ($record && $record->contact && $record->contact->created_at) {
                                    $component->state($record->contact->created_at->format('m/d/Y'));
                                }
                            }),
                        Forms\Components\Fieldset::make('Datos')
                            ->relationship('contact')
                            ->schema([
                                // Forms\Components\Select::make('full_name')
                                //     ->label('Nombre')
                                //     ->options(function () {
                                //         return Contact::all()->pluck('full_name', 'id')->toArray();
                                //     })
                                //     ->required()
                                //     ->columnSpan(2),
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->required()
                                    ->label('Fecha de Nacimiento')
                                    ->live(onBlur: true)
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('age')
                                    ->label('Edad')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('gender')
                                    ->label('Genero')
                                    ->required()
                                    ->options(Gender::class)
                                    ->placeholder('Seleccione')
                                    ->columnSpan(2),
                                Forms\Components\Select::make('marital_status')
                                    ->label('Estado Civil')
                                    ->required()
                                    ->options(MaritialStatus::class)
                                    ->placeholder('Seleccione')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('phone')
                                    ->required()
                                    ->label('Telefono')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('phone2')
                                    ->label('Telefono 2')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('email_address')
                                    ->email()
                                    ->required()
                                    ->label('Correo Electronico')
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('kommo_id')
                                    ->label('Kommo ID')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('kynect_case_number')
                                    ->label('Caso Kynect')
                                    ->live()
                                    ->columnSpan(2),
                            ])->columns(12),
                        Forms\Components\Fieldset::make('Direccion')
                            ->relationship('contact')
                            ->schema([
                                Forms\Components\TextInput::make('address_line_1')
                                    ->label('Direccion 1')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('address_line_2')
                                    ->label('Direccion 2')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('zip_code')
                                    ->required()
                                    ->label('Codigo Postal')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                                        if ($state !== null && strlen($state) === 5 && is_numeric($state)) {
                                            $zipCodeService = app(\App\Services\ZipCodeService::class);
                                            $locationData = $zipCodeService->getLocationFromZipCode($state);

                                            if ($locationData) {
                                                $set('city', $locationData['city']);
                                                $set('state_province', $locationData['state']);
                                                $set('county', $locationData['county']);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('city')
                                    ->required()
                                    ->label('Ciudad'),
                                Forms\Components\TextInput::make('county')
                                    ->required()
                                    ->label('Condado'),
                                Forms\Components\Select::make('state_province')
                                    ->required()
                                    ->options(UsState::class)
                                    ->label('Estado'),

                            ])->columns(4),

                        // Forms\Components\Fieldset::make('Información Migratoria')
                        //     ->schema([
                        //             Forms\Components\Select::make('contact_information.immigration_status')
                        //                 ->label('Estatus migratorio')
                        //                 ->options(ImmigrationStatus::class)
                        //                 ->live(),
                        //             Forms\Components\TextInput::make('contact_information.immigration_status_category')
                        //                 ->label('Descripción')
                        //                 ->columnSpan(2)
                        //                 ->disabled(fn (Get $get) => $get('contact_information.immigration_status') != ImmigrationStatus::Other->value)
                        //                 ->columnSpan(2),
                        //             Forms\Components\TextInput::make('contact_information.ssn')
                        //                 ->label('SSN #'),
                        //             Forms\Components\TextInput::make('contact_information.passport')
                        //                 ->label('Pasaporte'),
                        //             Forms\Components\TextInput::make('contact_information.alien_number')
                        //                 ->label('Alien'),
                        //             Forms\Components\TextInput::make('contact_information.work_permit_number')
                        //                 ->label('Permiso de Trabajo #'),
                        //             Forms\Components\DatePicker::make('contact_information.work_permit_emission_date')
                        //                 ->label('Emisión'),
                        //             Forms\Components\DatePicker::make('contact_information.work_permit_expiration_date')
                        //                 ->label('Vencimiento'),
                        //             Forms\Components\TextInput::make('contact_information.green_card_number')
                        //                 ->label('Green Card #'),
                        //             Forms\Components\DatePicker::make('contact_information.green_card_emission_date')
                        //                 ->label('Emisión'),
                        //             Forms\Components\DatePicker::make('contact_information.green_card_expiration_date')
                        //                 ->label('Vencimiento'),
                        //             Forms\Components\TextInput::make('contact_information.driver_license_number')
                        //                 ->label('Green Card #'),
                        //             Forms\Components\DatePicker::make('contact_information.driver_license_emission_date')
                        //                 ->label('Emisión'),
                        //             Forms\Components\DatePicker::make('contact_information.driver_license_expiration_date')
                        //                 ->label('Vencimiento'),
                        //         ])->columns(3),

                    ])
                    ->columns(5),
            ]);

    }

    // protected function afterSave(): void
    // {
    //     $policy = $this->record;

    //     // Check if the contact_id has changed
    //     if ($policy->contact_is_applicant) {
    //         $contactId = $policy->contact_id;

    //         // Find the existing 'self' applicant
    //         $selfApplicant = $policy->policyApplicants()
    //             ->where('relationship_with_policy_owner', 'self')
    //             ->first();

    //         if ($selfApplicant) {
    //             // Update the existing 'self' applicant with the new contact_id
    //             $selfApplicant->update(['is_covered_by_policy' => true]);
    //         }
    //     }
    // }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle the add_as_applicant toggle
        $policy = $this->record;
        $contact = $policy->contact;

        // First, check if there are any existing 'self' applicants that are not the current contact
        $existingSelfApplicants = $policy->policyApplicants()
            ->where('relationship_with_policy_owner', 'self')
            ->where('contact_id', '!=', $contact->id)
            ->get();

        // If there are any, change their relationship to 'other'
        foreach ($existingSelfApplicants as $applicant) {
            $applicant->update([
                'relationship_with_policy_owner' => 'other',
                'sort_order' => 1,
            ]);
        }

        // Add the current contact as an applicant with 'self' relationship if not already added
        $existingApplicant = $policy->policyApplicants()
            ->where('contact_id', $contact->id)
            ->first();

        if (! $existingApplicant) {
            // Create a new applicant with 'self' relationship
            $policy->policyApplicants()->create([
                'contact_id' => $contact->id,
                'relationship_with_policy_owner' => 'self',
                'sort_order' => 0,
                'is_covered_by_policy' => true,
            ]);
        } elseif ($existingApplicant->relationship_with_policy_owner !== 'self') {
            // Update the relationship to 'self' if it's not already
            $existingApplicant->update([
                'relationship_with_policy_owner' => 'self',
                'is_covered_by_policy' => true,
                'sort_order' => 0,

            ]);
        }

        // Remove the field from the data as it's not a database field

        return $data;
    }

    protected function afterSave(): void
    {
        // Mark this page as completed
        $this->record->markPageCompleted('edit_policy_contact');
    }

    //     protected function mutateFormDataBeforeSave(array $data): array
    //     {
    //         dd($data);
    //         if(isset($data['has_existing_kynect_case'])) {
    //             $policy = $this->record;
    //             $policy->has_kinect_case  = $data['has_existing_kynect_case'];
    //             $policy->save();
    //         }
    //         unset($data['has_existing_kynect_case']);
    //         return $data;
    //     }
}
