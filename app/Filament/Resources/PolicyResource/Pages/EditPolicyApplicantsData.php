<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Enums\UsState;
use App\Filament\Resources\PolicyResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;


class EditPolicyApplicantsData extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Datos';

    protected static ?string $navigationIcon = 'iconsax-bro-personalcard';

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['additional_applicants']) && is_array($data['additional_applicants'])) {
            // Count applicants where medicaid_client is true
            $medicaidCount = 0;
            foreach ($data['additional_applicants'] as $applicant) {
                if (isset($applicant['medicaid_client']) && $applicant['medicaid_client'] === true) {
                    $medicaidCount++;
                }
            }
            $data['total_applicants_with_medicaid'] = $medicaidCount;
            $data['total_applicants'] = count($data['additional_applicants']);
        }

        return $data;
    }

    public  function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos Aplicantes')
                    ->schema([
                        Forms\Components\Repeater::make('policyApplicants')
//                            ->itemLabel(fn (array $state): ?string => $state['first_name'] . ' ' . $state['middle_name'] . ' ' . $state['last_name'] . ' ' . $state['second_last_name'])
                            ->label('Aplicantes Adicionales')
                            ->relationship()
                            ->columnSpanFull()
                            ->hiddenLabel(true)
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->schema([
                                Forms\Components\Fieldset::make('Datos')
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('contact.full_name')
                                                    ->columnSpan(3)
                                                    ->readOnly()
                                                    ->label('Cliente')
                                                    ->live(),
                                                Forms\Components\Select::make('relationship_with_policy_owner')
                                                    ->columnSpan(2)
                                                    ->label('¿Relación con aplicante?')
                                                    ->options(FamilyRelationship::class)
                                                    ->disabled(fn ($record) => $record && $record->contact && $record->policy && $record->contact->id === $record->policy->contact_id)
                                                    ->required(),
                                                Forms\Components\Toggle::make('is_covered_by_policy')
                                                    ->inline(false)
                                                    ->columnSpan(1)
                                                    ->label('¿Aplicante?')
                                                    ->required(),
                                                Forms\Components\Toggle::make('medicaid_client')
                                                    ->inline(false)
                                                    ->label('¿Medicaid?'),
                                                // Section to show and edit Member data
                                                Forms\Components\Section::make()
                                                    ->collapsible()
                                                    ->relationship('contact')
                                                    ->schema([
                                                        Forms\Components\Select::make('gender')
                                                            ->label('Género')
                                                            ->columnSpan(2)
                                                            ->options(Gender::class)
                                                            ->required(),
                                                        Forms\Components\DatePicker::make('date_of_birth')
                                                            ->label('Fecha de Nacimiento')
                                                            ->required()
                                                            ->columnSpan(2)
                                                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                                                if ($state) {
                                                                    $age = Carbon::parse($state)->age;
                                                                    $set('age', $age);
                                                                }
                                                            })
                                                            ->live(onBlur: true),
                                                        Forms\Components\TextInput::make('age')
                                                            ->label('Edad')
                                                            ->disabled(true)
                                                            ->dehydrated(false),
                                                        Forms\Components\TextInput::make('country_of_birth')
                                                            ->label('País de Nacimiento')
                                                            ->columnSpan(2),
                                                        Forms\Components\Select::make('marital_status')
                                                            ->label('Estado Civil')
                                                            ->options([
                                                                'single' => 'Soltero',
                                                                'married' => 'Casado',
                                                                'divorced' => 'Divorciado',
                                                                'widowed' => 'Viudo',
                                                                'separated' => 'Separado',
                                                            ])
                                                            ->columnSpan(2),
                                                        Forms\Components\TextInput::make('phone')
                                                            ->label('Teléfono')
                                                            ->tel()
                                                            ->columnSpan(2),
                                                        Forms\Components\TextInput::make('phone2')
                                                            ->label('Teléfono 2')
                                                            ->tel()
                                                            ->columnSpan(2),
                                                        Forms\Components\TextInput::make('email_address')
                                                            ->columnSpan(3)
                                                            ->label('Email')
                                                            ->email(),
                                                        Forms\Components\Toggle::make('is_tobacco_user')
                                                            ->inline(false)
                                                            ->label('¿Fuma?'),
                                                        Forms\Components\Toggle::make('is_pregnant')
                                                            ->inline(false)
                                                            ->label('¿Embarazada?'),

                                                        Forms\Components\Fieldset::make('Información Migratoria')
                                                            ->schema([
                                                                Forms\Components\Select::make('immigration_status')
                                                                    ->label('Estatus migratorio')
                                                                    ->columnSpan(3)
                                                                    ->options(ImmigrationStatus::class)
                                                                    ->live(),
                                                                Forms\Components\TextInput::make('immigration_status_category')
                                                                    ->label('Descripción')
                                                                    ->columnSpan(4)
                                                                    ->disabled(fn (Get $get) => $get('immigration_status') != ImmigrationStatus::Other->value),
                                                                Forms\Components\TextInput::make('ssn')
                                                                    ->label('SSN #')
                                                                    ->columnSpan(2),
                                                                Forms\Components\TextInput::make('passport_number')
                                                                    ->label('Núm. Pasaporte')
                                                                    ->columnSpan(3),
                                                                Forms\Components\TextInput::make('alien_number')
                                                                    ->label('Núm. Alien')
                                                                    ->columnSpan(2),
                                                                Forms\Components\TextInput::make('work_permit_number')
                                                                    ->columnSpan(2)
                                                                    ->label('Num. Permiso Trabajo'),
                                                                Forms\Components\DatePicker::make('work_permit_emissions_date')
                                                                    ->label('Emisión')
                                                                    ->columnSpan(2)
                                                                    ->columnStart(4),
                                                                Forms\Components\DatePicker::make('work_permit_expiration_date')
                                                                    ->label('Vencimiento')
                                                                    ->columnSpan(2),
                                                                Forms\Components\TextInput::make('driver_license_number')
                                                                    ->label('Licencia de conducir')
                                                                    ->columnSpan(2),
                                                                Forms\Components\DatePicker::make('driver_license_emission_date')
                                                                    ->label('Emisión')
                                                                    ->columnSpan(2)
                                                                    ->columnStart(4),
                                                                Forms\Components\Select::make('driver_license_emissions_state')
                                                                    ->options(UsState::class)
                                                                    ->label('Estado Emisión')
                                                                    ->columnSpan(2),
                                                                    
                                                            ])->columns(7),
                                                    ])->columns(9),

//                                                Forms\Components\Select::make('immigration_status')
//                                                    ->columnSpan(2)
//                                                    ->label('Estado de Inmigración')
//                                                    ->options(ImmigrationStatus::class)
//                                                    ->required(),
//                                                Forms\Components\TextInput::make('code')
//                                                    ->label('Código')
//                                                    ->readonly(),
//



                                                ])
                                            ->columns(7)
                                            ->columnSpanFull(),

//

//



                                    ])->columns(4),



                            ])
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->collapsible(false)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(3)
                        ])
            ]);
    }


}
