<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Filament\Resources\PolicyResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Carbon;


class EditPolicyApplicants2 extends EditRecord
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
        }

        return $data;
    }

    public  function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Aplicantes Adicionales')
                    ->schema([
                        Forms\Components\Repeater::make('policyApplicants')
//                            ->itemLabel(fn (array $state): ?string => $state['first_name'] . ' ' . $state['middle_name'] . ' ' . $state['last_name'] . ' ' . $state['second_last_name'])
                            ->label('Aplicantes Adicionales')
                            ->relationship()
                            ->columnSpanFull()
                            ->hiddenLabel(true)
                            ->orderColumn('sort_order')
                            ->schema([
                                Forms\Components\Fieldset::make('Datos')
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\Select::make('id')
                                                    ->relationship('contact', 'full_name')
                                                    ->columnSpan(3)
                                                    ->label('Cliente')
                                                    ->live()
                                                    ->getOptionLabelFromRecordUsing(function (Contact $record) {
                                                        $label = $record->full_name;
                                                        if ($record->state_province) {
                                                            $label .= ' / '.$record->state_province->getLabel();
                                                        }

                                                            if ($record->age) {
                                                                $label .= ' / '.$record->age;
                                                            }

                                                            return $label;
                                                    })
                                                    ->createOptionForm([
                                                        Forms\Components\TextInput::make('full_name')
                                                            ->required(),
                                                    ])
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        if ($state) {
                                                            $contact = Contact::find($state);
                                                            if ($contact) {
                                                                $set('full_name', $contact->full_name);
                                                                $set('immigration_status', $contact->immigration_status);
                                                            }
                                                        }
                                                    })
                                                    ->searchable(),
                                                Forms\Components\Select::make('relationship_with_policy_owner')
                                                    ->columnSpan(2)
                                                    ->label('¿Relación con aplicante?')
                                                    ->options(FamilyRelationship::class)
                                                    ->required(),
                                                Forms\Components\Section::make()
                                                    ->relationship('contact')
                                                    ->schema([
                                                        Forms\Components\TextInput::make('full_name')
                                                            ->label('Nombre Completo'),
                                                    ]),

//                                                Forms\Components\Select::make('immigration_status')
//                                                    ->columnSpan(2)
//                                                    ->label('Estado de Inmigración')
//                                                    ->options(ImmigrationStatus::class)
//                                                    ->required(),
//                                                Forms\Components\TextInput::make('code')
//                                                    ->label('Código')
//                                                    ->readonly(),
//
//                                                Forms\Components\Select::make('gender')
//                                                    ->columnSpan(2)
//                                                    ->label('Género')
//                                                    ->options(Gender::class)
//                                                    ->required(),
//                                                Forms\Components\Toggle::make('is_tobacco_user')
//                                                    ->inline(false)
//                                                    ->label('¿Fuma?'),
//                                                Forms\Components\Toggle::make('is_pregnant')
//                                                    ->inline(false)
//                                                    ->label('¿Embarazada?'),
//                                                Forms\Components\Toggle::make('medicaid_client')
//                                                    ->inline(false)
//                                                    ->label('¿Medicaid?'),
                                                ])
                                            ->columns(7)
                                            ->columnSpanFull(),

//                                                Forms\Components\TextInput::make('first_name')
//                                                    ->label('Primer Nombre')
//                                                    ->required(),
//                                                Forms\Components\TextInput::make('middle_name')
//                                                    ->label('Segundo Nombre'),
//                                                Forms\Components\TextInput::make('last_name')
//                                                    ->label('Primer Apellido')
//                                                    ->required(),
//                                                Forms\Components\TextInput::make('second_last_name')
//                                                    ->label('Segundo Apellido'),
//                                                Forms\Components\DatePicker::make('date_of_birth')
//                                                    ->label('Fecha de Nacimiento')
//                                                    ->required()
//                                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
//                                                        if ($state) {
//                                                            $age = Carbon::parse($state)->age;
//                                                            $set('age', $age);
//                                                        }
//                                                    })
//                                                    ->live(onBlur: true),
//                                                Forms\Components\TextInput::make('age')
//                                                    ->label('Edad')
//                                                    ->disabled(true)
//                                                    ->dehydrated(false),
//
//                                                Forms\Components\TextInput::make('country_of_birth')
//                                                    ->label('País de Nacimiento'),
//                                                Forms\Components\Select::make('civil_status')
//                                                    ->label('Estado Civil')
//                                                    ->options([
//                                                        'single' => 'Soltero',
//                                                        'married' => 'Casado',
//                                                        'divorced' => 'Divorciado',
//                                                        'widowed' => 'Viudo',
//                                                        'separated' => 'Separado',
//                                                    ]),
//                                                Forms\Components\TextInput::make('phone1')
//                                                    ->label('Teléfono')
//                                                    ->tel(),
//                                                Forms\Components\TextInput::make('email_address')
//                                                    ->columnSpan(2)
//                                                    ->label('Email')
//                                                    ->email(),

                                    ])->columns(4),

//                                Forms\Components\Fieldset::make('Información Migratoria')
//                                    ->schema([
//                                        Forms\Components\Select::make('member_inmigration_status')
//                                            ->label('Estatus migratorio')
//                                            ->options(ImmigrationStatus::class)
//                                            ->live(),
//                                        Forms\Components\TextInput::make('member_inmigration_status_category')
//                                            ->label('Descripción')
//                                            ->columnSpan(2)
//                                            ->disabled(fn (Get $get) => $get('member_inmigration_status') != ImmigrationStatus::Other->value)
//                                            ->columnSpan(2),
//                                        Forms\Components\TextInput::make('member_ssn')
//                                            ->label('SSN #'),
//                                        Forms\Components\TextInput::make('member_passport')
//                                            ->label('Pasaporte'),
//                                        Forms\Components\TextInput::make('member_alien_number')
//                                            ->label('Alien'),
//                                        Forms\Components\TextInput::make('member_work_permit_number')
//                                            ->label('Permiso de Trabajo #'),
//                                        Forms\Components\DatePicker::make('member_work_permit_emission_date')
//                                            ->label('Emisión'),
//                                        Forms\Components\DatePicker::make('member_work_permit_expiration_date')
//                                            ->label('Vencimiento'),
//                                        Forms\Components\TextInput::make('member_green_card_number')
//                                            ->label('Green Card #'),
//                                        Forms\Components\DatePicker::make('member_green_card_emission_date')
//                                            ->label('Emisión'),
//                                        Forms\Components\DatePicker::make('member_green_card_expiration_date')
//                                            ->label('Vencimiento'),
//                                        Forms\Components\TextInput::make('member_driver_license_number')
//                                            ->label('Licencia de conducir #'),
//                                        Forms\Components\DatePicker::make('member_driver_license_emission_date')
//                                            ->label('Emisión'),
//                                        Forms\Components\DatePicker::make('member_driver_license_expiration_date')
//                                            ->label('Vencimiento'),
//                                    ])->columns(3),

                            ])
                            ->reorderable(false)
                            ->addActionLabel('Agregar Aplicante')
                            ->collapsible(true)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(3)
                        ])
            ]);
    }


}
