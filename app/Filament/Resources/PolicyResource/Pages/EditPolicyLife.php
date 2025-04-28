<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Enums\UsState;
use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Form;
use Filament\Forms;
use App\Models\Policy;
use App\Enums\PolicyType;
use Illuminate\Support\HtmlString;
use Filament\Support\Enums\Alignment;
use App\Models\Contact;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class EditPolicyLife extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Poliza de Vida';

    protected static ?string $navigationIcon = 'iconoir-heart';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make()
                    ->visible(fn (Policy $record) => $record->policy_type == PolicyType::Life)
                    ->schema([
                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\TextInput::make('life_insurance.applicant.height_cm')
                                    ->label('Altura (cm)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(10)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        $set('life_insurance.applicant.height_feet', number_format($state / 30.48, 2));
                                    })
                                    ->formatStateUsing(fn($state) => number_format($state, 2)),
                                Forms\Components\TextInput::make('life_insurance.applicant.weight_kg')
                                    ->label('Peso (kg)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(10)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        $set('life_insurance.applicant.weight_lbs',
                                            number_format($state / 0.45359237, 2));
                                    })
                                    ->formatStateUsing(fn($state) => number_format($state, 2)),
                                Forms\Components\TextInput::make('life_insurance.applicant.height_feet')
                                    ->label('Altura (pies)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        $set('life_insurance.applicant.height_cm', number_format($state * 30.48, 2));
                                    })
                                    ->formatStateUsing(fn($state) => number_format($state, 2)),
                                Forms\Components\TextInput::make('life_insurance.applicant.weight_lbs')
                                    ->label('Peso (lb)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(10)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        $set('life_insurance.applicant.weight_kg',
                                            number_format($state * 0.45359237, 2));
                                    })
                                    ->formatStateUsing(fn($state) => number_format($state, 2)),
                            ])
                            ->columnSpan(2)
                            ->columns(2),
                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\Toggle::make('life_insurance.applicant.smoker')
                                    ->label('Fuma'),
                                Forms\Components\Toggle::make('life_insurance.applicant.practice_extreme_sport')
                                    ->label('Deportes extremos'),
                                Forms\Components\Toggle::make('life_insurance.applicant.has_made_felony')
                                    ->label('Cometido felonía'),
                                Forms\Components\Toggle::make('life_insurance.applicant.has_declared_bankruptcy')
                                    ->label('Declarado bancarrota'),
                                Forms\Components\Toggle::make('life_insurance.applicant.plans_to_travel_abroad')
                                    ->label('Planear viajar al extranjero'),
                                Forms\Components\Toggle::make('life_insurance.applicant.allows_videocall')
                                    ->label('Permite videollamada'),
                            ])
                            ->columnSpan(2)
                            ->columns(2),

                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\TextInput::make('life_insurance.applicant.primary_doctor')
                                    ->label('Doctor principal'),
                                Forms\Components\TextInput::make('life_insurance.applicant.primary_doctor_phone')
                                    ->label('Teléfono doctor'),
                                Forms\Components\TextInput::make('life_insurance.applicant.primary_doctor_address')
                                    ->label('Dirección del doctor principal')
                                    ->columnSpan(4),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\DatePicker::make('life_insurance.applicant.diagnosis_date')
                                            ->label('Fecha último diagnóstico'),
                                        Forms\Components\Textarea::make('life_insurance.applicant.diagnosis')
                                            ->label('Diagnóstico')
                                            ->rows(3),
                                    ])
                                    ->columns(1)
                                    ->columnSpan(3),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('life_insurance.applicant.disease')
                                            ->label('Enfermedad'),
                                        Forms\Components\Textarea::make('life_insurance.applicant.drugs_prescribed')
                                            ->label('Medicamentos prescritos')
                                            ->rows(3),
                                    ])
                                    ->columns(1)
                                    ->columnSpan(3),

                                Forms\Components\Toggle::make('life_insurance.applicant.has_been_hospitalized')
                                    ->label('Ha sido hospitalizado')
                                    ->columnStart(4)
                                    ->live()
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('life_insurance.applicant.hospitalized_date')
                                    ->label('Fecha hospitalización')
                                    ->disabled(fn($get) => !$get('life_insurance.applicant.has_been_hospitalized')),

                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('life_insurance.father.is_alive')
                                            ->label('¿Padre falleció?')
                                            ->live()
                                            ->inline(false),
                                        Forms\Components\TextInput::make('life_insurance.father.age')
                                            ->label('Edad')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('life_insurance.father.death_reason')
                                            ->label('Motivo de fallecimiento')
                                            ->disabled(fn($get) => !$get('life_insurance.father.is_alive'))
                                            ->columnSpan(4),
                                        Forms\Components\Toggle::make('life_insurance.mother.is_alive')
                                            ->label('¿Madre falleció?')
                                            ->live()
                                            ->inline(false),
                                        Forms\Components\TextInput::make('life_insurance.mother.age')
                                            ->label('Edad')
                                            ->numeric(),
                                        Forms\Components\TextInput::make('life_insurance.mother.death_reason')
                                            ->label('Motivo de fallecimiento')
                                            ->disabled(fn($get) => !$get('life_insurance.mother.is_alive'))
                                            ->columnSpan(4),
                                        Forms\Components\Toggle::make('life_insurance.family.member_final_disease')
                                            ->label('Familiar con enfermedad')
                                            ->live()
                                            ->columnSpan(2)
                                            ->inline(false),
                                        Forms\Components\Textinput::make('life_insurance.family.member_final_disease_relationship')
                                            ->label('Parentesco')
                                            ->disabled(fn($get) => !$get('life_insurance.family.member_final_disease'))
                                            ->columnSpan(4),
                                        Forms\Components\TextInput::make('life_insurance.family.member_final_disease_description')
                                            ->label('Descripción de la enfermedad')
                                            ->disabled(fn($get) => !$get('life_insurance.family.member_final_disease'))
                                            ->columnSpan(6),

                                    ])->columns(12),





                            ])->columns(6),


                        Forms\Components\Fieldset::make()
                            ->schema([
                                Forms\Components\TextInput::make('life_insurance.employer.name')
                                    ->label('Empleador'),
                                Forms\Components\TextInput::make('life_insurance.employer.job_title')
                                    ->label('Cargo'),
                                Forms\Components\TextInput::make('life_insurance.employer.employment_phone')
                                    ->label('Teléfono de trabajo'),
                                Forms\Components\TextInput::make('life_insurance.employer.employment_address')
                                    ->label('Dirección de trabajo')
                                    ->columnSpan(2),
                                Forms\Components\DatePicker::make('life_insurance.employer.employment_start_date')
                                    ->label('Fecha inicio de empleo')
                            ])
                            ->columns(6),


                        Forms\Components\Fieldset::make('Beneficiarios')
                            ->schema([
                                Forms\Components\TextInput::make('life_insurance.applicant.patrimony')
                                    ->label('Patrimonio')
                                    ->numeric(),

                                Forms\Components\TextInput::make('life_insurance.total_beneficiaries')
                                    ->label('Número Beneficiarios')
                                    ->live(onBlur: true)
                                    ->default(1)
                                    ->maxValue(6)
                                    ->numeric()
                                    ->required()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $numberBeneficiaries = $state;

                                        $percentageAssigned = (float) $get('life_insurance.beneficiaries.total_percentage');
                                        $countOfPercentageZero = 0;
                                        for ($i = 1; $i <= $numberBeneficiaries; $i++) {
                                            $currentPercentage = (float) $get('life_insurance.beneficiaries.'.$i.'.percentage');
                                            if ($currentPercentage == 0) {
                                                $countOfPercentageZero++;
                                            }
                                        }

                                        if ($countOfPercentageZero > 0) {
                                            if ((100 - $percentageAssigned) > 0) {
                                                for ($i = 1; $i <= $numberBeneficiaries; $i++) {
                                                    $currentPercentage = (float) $get('life_insurance.beneficiaries.'.$i.'.percentage');
                                                    if ($currentPercentage == 0) {
                                                        $set('life_insurance.beneficiaries.'.$i.'.percentage',
                                                            number_format((100 - $percentageAssigned) / $countOfPercentageZero, 2));
                                                        $set('life_insurance.beneficiaries.total_percentage', 100);
                                                    }
                                                }
                                            }
                                        } else {
                                            $set('life_insurance.beneficiaries.total_percentage', number_format($percentageAssigned, 2));
                                        }
                                    }),

                                Forms\Components\TextInput::make('life_insurance.total_contingents')
                                    ->label('Número Contingentes')
                                    ->live(onBlur: true)
                                    ->default(0)
                                    ->numeric()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        $numberContingents = $state;

                                        $percentageAssigned = (float) $get('life_insurance.contingents.total_percentage');
                                        $countOfPercentageZero = 0;
                                        for ($i = 1; $i <= $numberContingents; $i++) {
                                            $currentPercentage = (float) $get('life_insurance.contingents.'.$i.'.percentage');
                                            if ($currentPercentage == 0) {
                                                $countOfPercentageZero++;
                                            }
                                        }

                                        if ($countOfPercentageZero > 0) {
                                            if ((100 - $percentageAssigned) > 0) {
                                                for ($i = 1; $i <= $numberContingents; $i++) {
                                                    $currentPercentage = (float) $get('life_insurance.contingents.'.$i.'.percentage');
                                                    if ($currentPercentage == 0) {
                                                        $set('life_insurance.contingents.'.$i.'.percentage',
                                                            number_format((100 - $percentageAssigned) / $countOfPercentageZero, 2));
                                                        $set('life_insurance.contingents.total_percentage', 100);
                                                    }
                                                }
                                            }
                                        } else {
                                            $set('life_insurance.contingents.total_percentage', number_format($percentageAssigned, 2));
                                        }
                                    }),

                            ])
                            ->columns(3)
                            ->columnSpan(3)
                            ->columnStart(2),


                        Forms\Components\Section::make('Beneficiarios')
                            ->schema([
                                // Beneficiario 1
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.1.name')
                                    ->label('Nombre del Beneficiario 1')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.1.date_of_birth')
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.1.relationship')
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.1.id_number')
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.1.phone_number')
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.1.email')
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.1.percentage')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 2
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->label('Nombre del Beneficiario 2')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.2.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.2.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 3
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->label('Nombre del Beneficiario 3')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.3.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.3.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.percentage')
                                    ->label('Asignación')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 4
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->label('Nombre del Beneficiario 4')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.4.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.4.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 5
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->label('Nombre del Beneficiario 5')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.5.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.5.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 6
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->label('Nombre del Beneficiario 6')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.6.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.6.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Total
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.total_percentage')
                                    ->prefix('%')
                                    ->numeric()
                                    ->label('Total %')
                                    ->columnStart(14)
                                    ->columnSpan(2)
                                    ->minValue(100)
                                    ->maxValue(100)
                                    ->disabled()
                                    ->dehydrated(true),

                            ])->columns(15),

                        Forms\Components\Section::make('Contingentes')
                            ->schema([
                                // Contingente 1
                                Forms\Components\TextInput::make('life_insurance.contingents.1.name')
                                    ->label('Nombre del Contingente 1')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.1.date_of_birth')
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.1.relationship')
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.1.id_number')
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.1.phone_number')
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.1.email')
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.1.percentage')
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 2
                                Forms\Components\TextInput::make('life_insurance.contingents.2.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->label('Nombre del Contingente 2')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.2.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.2.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 3
                                Forms\Components\TextInput::make('life_insurance.contingents.3.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->label('Nombre del Contingente 3')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.3.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.3.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 4
                                Forms\Components\TextInput::make('life_insurance.contingents.4.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->label('Nombre del Contingente 4')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.4.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.4.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 5
                                Forms\Components\TextInput::make('life_insurance.contingents.5.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->label('Nombre del Contingente 5')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.5.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.5.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 6
                                Forms\Components\TextInput::make('life_insurance.contingents.6.name')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->label('Nombre del Contingente 6')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.6.date_of_birth')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.6.relationship')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.id_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.phone_number')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.email')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.percentage')
                                    ->hidden(fn(Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn(
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Total
                                Forms\Components\TextInput::make('life_insurance.contingents.total_percentage')
                                    ->prefix('%')
                                    ->numeric()
                                    ->label('Total %')
                                    ->columnStart(14)
                                    ->columnSpan(2)
                                    ->minValue(100)
                                    ->maxValue(100)
                                    ->disabled()
                                    ->dehydrated(true),


                            ])
                            ->columns(15)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull()
                    ->columns(4),
                Forms\Components\Section::make()
                    ->visible(fn (Policy $record) => $record->policy_type != PolicyType::Life)
                    ->schema([
                        Forms\Components\Placeholder::make('not_life_policy')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->dehydrated(true)
                            ->content(new HtmlString('
                                <div style="text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 8px; margin: 20px 0;">
                                    <h2 style="color: #4b5563; margin-bottom: 15px; font-weight: 600;">Esta no es una póliza de vida</h2>
                                    <p style="color: #6b7280; font-size: 16px; margin-bottom: 15px;">
                                        Esta póliza actualmente no está configurada como una póliza de vida.
                                    </p>
                                    <p style="color: #6b7280; font-size: 16px;">
                                        Si deseas crear una póliza de vida con los datos actuales, utiliza el botón <strong>"Crear Póliza de Vida"</strong> arriba.
                                    </p>
                                </div>
                            ')),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('create_life_policy')
                                ->label('Crear Poliza de Vida')
                                ->icon('heroicon-m-plus')
                                ->color('success')
                                ->requiresConfirmation()
                                ->action(function (array $data, Policy $record) {
                                    // Create a new policy by replicating the current one
                                    $new_policy = $record->replicate(['code']); // Exclude code from replication
                                    $new_policy->policy_type = PolicyType::Life;
                                    // Code will be auto-generated by the model's boot method
                                    $new_policy->save();
                                    Notification::make()
                                        ->title('Póliza de vida creada exitosamente')
                                        ->success()
                                        ->send();
                                    return redirect()->to(PolicyResource::getUrl('edit', ['record' => $new_policy]));
                                }),
                            Forms\Components\Actions\Action::make('duplicate_policy')
                            ->label('Duplicar')
                            ->icon('heroicon-o-document-duplicate')
                            ->form([
                                Forms\Components\Select::make('life_contact_id')
                                    ->columnSpan(4)
                                    ->label('Cliente')
                                    ->live()
                                    ->options(function (Policy $record) {
                                        return Policy::find($record->id)->applicants()->pluck('full_name', 'contact_id');
                                    })
                                    ->default(function (Policy $record) {
                                        return (int) $record->contact_id;
                                    })
                                    ->live(),
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Fecha de inicio')
                                    ->required()
                                    ->default(now()),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Fecha de fin')
                                    ->default(now()->addYear()->subDay()),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas')
                                    ->rows(3),
                            ])
                            ->modalHeading('Crear Póliza de Vida')
                            ->modalDescription('Se creará una nueva póliza de vida con los datos del aplicante seleccionado. Por favor, seleccione el aplicante y especifique las nuevas fechas.')
                            ->modalSubmitActionLabel('Crear Poliza de Vida')
                            ->action(function (array $data, Policy $record) {
                                $new_policy = $record->replicate(['code']); // Exclude code from replication
                                $new_policy->contact_id = $data['life_contact_id'];
                                $new_policy->start_date = $data['start_date'];
                                $new_policy->end_date = $data['end_date'];
                                $new_policy->policy_type = PolicyType::Life; // Set policy type to Life
                                $new_policy->notes = ($new_policy->notes ? $new_policy->notes . "\n\n" : '') . 
                                    "=== Notas de Creación ===\n" . ($data['notes'] ?? 'Póliza de vida creada el ' . now()->format('Y-m-d H:i:s'));
                                $new_policy->save();
                                Notification::make()
                                    ->title('Póliza de vida creada exitosamente')
                                    ->success()
                                    ->send();
                                return redirect()->to(PolicyResource::getUrl('edit', ['record' => $new_policy]));
                            })
                            ])->alignment(Alignment::Center),
                    ]),


            ])
            ->columns(1);
    }

    protected static function updateBeneficiariesAssignation(Forms\Set $set, Forms\Get $get): void
    {

        $total_beneficiaries = (float) $get('life_insurance.total_beneficiaries');
        $percentageAssigned = 0;
        $countOfPercentageZero = 0;
        for ($i = 1; $i <= $total_beneficiaries; $i++) {
            // Convert to float to ensure we're working with numbers, not strings
            $currentPercentage = (float) $get('life_insurance.beneficiaries.'.$i.'.percentage');
            // Add to the total percentage assigned
            $percentageAssigned += $currentPercentage;
            if ($currentPercentage == 0) {
                $countOfPercentageZero++;
            }
        }

        if ($countOfPercentageZero > 0) {
            if ((100 - $percentageAssigned) > 0) {
                for ($i = 1; $i <= $total_beneficiaries; $i++) {
                    $currentPercentage = (float) $get('life_insurance.beneficiaries.'.$i.'.percentage');
                    if ($currentPercentage == 0) {
                        $set('life_insurance.beneficiaries.'.$i.'.percentage',
                            number_format((100 - $percentageAssigned) / $countOfPercentageZero, 2));
                        $set('life_insurance.beneficiaries.total_percentage', 100);
                    }
                }
            }
        } else {
            $set('life_insurance.beneficiaries.total_percentage', number_format($percentageAssigned, 2));
        }
    }

    protected static function updateContingentsAssignation(Forms\Set $set, Forms\Get $get): void
    {

        $total_contingents = (float) $get('life_insurance.total_contingents');
        $percentageAssigned = 0;
        $countOfPercentageZero = 0;
        for ($i = 1; $i <= $total_contingents; $i++) {
            // Convert to float to ensure we're working with numbers, not strings
            $currentPercentage = (float) $get('life_insurance.contingents.'.$i.'.percentage');
            // Add to the total percentage assigned
            $percentageAssigned += $currentPercentage;
            if ($currentPercentage == 0) {
                $countOfPercentageZero++;
            }
        }

        if ($countOfPercentageZero > 0) {
            if ((100 - $percentageAssigned) > 0) {
                for ($i = 1; $i <= $total_contingents; $i++) {
                    $currentPercentage = (float) $get('life_insurance.contingents.'.$i.'.percentage');
                    if ($currentPercentage == 0) {
                        $set('life_insurance.contingents.'.$i.'.percentage',
                            number_format((100 - $percentageAssigned) / $countOfPercentageZero, 2));
                        $set('life_insurance.contingents.total_percentage', 100);
                    }
                }
            }
        } else {
            $set('life_insurance.contingents.total_percentage', number_format($percentageAssigned, 2));
        }
    }
}
