<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Enums\MaritialStatus;
use App\Enums\UsState;
use App\Filament\Resources\PolicyResource;
use App\Models\PolicyApplicant;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms;

class EditPolicyContact extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Cliente';

    protected static ?string $navigationIcon = 'eos-perm-contact-calendar-o';



    public function form(Form $form): Form
    {
        return $form
            ->schema([


                Forms\Components\Section::make('Datos del Cliente')
                    ->schema([
                        Forms\Components\Fieldset::make('Datos de Contacto')
                            ->relationship('contact')
                            ->schema([
                                Forms\Components\TextInput::make('full_name')
                                    ->label('Nombre')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->label('Fecha de Nacimiento')
                                    ->live(onBlur: true),
                                Forms\Components\TextInput::make('age')
                                    ->label('Edad')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('gender')
                                    ->label('Genero')
                                    ->options(Gender::class),
                                Forms\Components\Select::make('marital_status')
                                    ->label('Estado Civil')
                                    ->options(MaritialStatus::class),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefono'),
                                Forms\Components\TextInput::make('kommo_id')
                                    ->label('Kommo ID'),
                                Forms\Components\TextInput::make('email_address')
                                    ->email()
                                    ->label('Correo Electronico')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('kynect_case_number')
                                    ->label('Caso Kynect')
                                    ->live(),
                            ])->columns(4),


                        Forms\Components\Toggle::make('add_as_applicant')
                            ->inline(false)
                            ->columnStart(3)
                            ->label('Agregar como Aplicante')
                            ->default(true),
                        Forms\Components\Toggle::make('has_existing_kynect_case')
                            ->inline(false)
                            ->label('Pedir Caso Kynect')
                            ->disabled(fn (Get $get) => $get('kynect_case_number')),
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
                                    ->label('Codigo Postal'),
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

                        Forms\Components\Fieldset::make('Información Migratoria')
                            ->schema([
                                    Forms\Components\Select::make('contact_information.immigration_status')
                                        ->label('Estatus migratorio')
                                        ->options(ImmigrationStatus::class)
                                        ->live(),
                                    Forms\Components\TextInput::make('contact_information.immigration_status_category')
                                        ->label('Descripción')
                                        ->columnSpan(2)
                                        ->disabled(fn (Get $get) => $get('contact_information.immigration_status') != ImmigrationStatus::Other->value)
                                        ->columnSpan(2),
                                    Forms\Components\TextInput::make('contact_information.ssn')
                                        ->label('SSN #'),
                                    Forms\Components\TextInput::make('contact_information.passport')
                                        ->label('Pasaporte'),
                                    Forms\Components\TextInput::make('contact_information.alien_number')
                                        ->label('Alien'),
                                    Forms\Components\TextInput::make('contact_information.work_permit_number')
                                        ->label('Permiso de Trabajo #'),
                                    Forms\Components\DatePicker::make('contact_information.work_permit_emission_date')
                                        ->label('Emisión'),
                                    Forms\Components\DatePicker::make('contact_information.work_permit_expiration_date')
                                        ->label('Vencimiento'),
                                    Forms\Components\TextInput::make('contact_information.green_card_number')
                                        ->label('Green Card #'),
                                    Forms\Components\DatePicker::make('contact_information.green_card_emission_date')
                                        ->label('Emisión'),
                                    Forms\Components\DatePicker::make('contact_information.green_card_expiration_date')
                                        ->label('Vencimiento'),
                                    Forms\Components\TextInput::make('contact_information.driver_license_number')
                                        ->label('Green Card #'),
                                    Forms\Components\DatePicker::make('contact_information.driver_license_emission_date')
                                        ->label('Emisión'),
                                    Forms\Components\DatePicker::make('contact_information.driver_license_expiration_date')
                                        ->label('Vencimiento'),
                                ])->columns(3),


                    ])
                    ->columns(4),
            ]);

    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle the add_as_applicant toggle
            $policy = $this->record;
            $contact = $policy->contact;
//            dd($contact);

            // Add the contact as an applicant if not already added
            $existingApplicant = $policy->policyApplicants()
                ->where('contact_id', $contact->id)
                ->where('relationship_with_policy_owner', 'self')
                ->first();

            if (!$existingApplicant) {
                $policy->policyApplicants()->create([
                    'contact_id' => $contact->id,
                    'relationship_with_policy_owner' => 'self',
                    'sort_order' => 0
                ]);
            }

        // Remove the field from the data as it's not a database field
        unset($data['add_as_applicant']);

        return $data;
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
