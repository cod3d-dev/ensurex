<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCompletePolicyCreation extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Finalizar';

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected function beforeSave(): void
    {

        // Check if all required pages have been completed
        if (! $this->record->areRequiredPagesCompleted()) {
            // Get incomplete pages
            $incompletePages = $this->record->getIncompletePages();

            // Define custom page names for each page
            $pageNameMapping = [
                'edit_policy' => 'Póliza',
                'edit_policy_contact' => 'Titular',
                'edit_policy_applicants' => 'Miembros',
                'edit_policy_applicants_data' => 'Datos',
                'edit_policy_income' => 'Ingresos',
                'edit_policy_payments' => 'Pago',
            ];

            // Map incomplete pages to their custom names
            $readablePageNames = array_map(function ($pageName) use ($pageNameMapping) {
                return $pageNameMapping[$pageName] ?? ucwords(str_replace('_', ' ', $pageName));
            }, $incompletePages);

            // Stop the save operation and display a notification

            // Show notification with incomplete pages

            Notification::make()
                ->warning()
                ->title('Información de póliza incompleta')
                ->body('Por favor, complete las siguientes secciones antes de continuar: '.implode(', ', $readablePageNames))
                ->persistent()
                ->send();

            $this->halt();

        }

    }

    // protected function getSaveFormAction(): Action
    // {
    //     return parent::getSaveFormAction()
    //         ->hidden(fn () => ! $this->record->areRequiredPagesCompleted())
    //         ->tooltip(fn () => ! $this->record->areRequiredPagesCompleted()
    //             ? 'Complete todas las páginas requeridas primero'
    //             : 'Guardar cambios');
    // }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\CheckboxList::make('quote_policy_types')
                            ->label('Polizas a crear')
                            ->options(PolicyType::class)
                            ->required()
                            ->live()
                            ->disabled(fn ($record) => $record->status !== PolicyStatus::Draft)
                            ->columns(8),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('Crear Polizas')
                                ->color('success')
                                ->action(function ($record, Forms\Get $get) {
                                    // Check if all required pages have been completed
                                    if (! $record->areRequiredPagesCompleted()) {
                                        // Get incomplete pages
                                        $incompletePages = $record->getIncompletePages();

                                        // Define custom page names for each page
                                        $pageNameMapping = [
                                            'edit_policy' => 'Póliza',
                                            'edit_policy_contact' => 'Titular',
                                            'edit_policy_applicants' => 'Miembros',
                                            'edit_policy_applicants_data' => 'Datos',
                                            'edit_policy_income' => 'Ingresos',
                                            'edit_policy_payments' => 'Pago',
                                        ];

                                        // Map incomplete pages to their custom names
                                        $readablePageNames = array_map(function ($pageName) use ($pageNameMapping) {
                                            return $pageNameMapping[$pageName] ?? ucwords(str_replace('_', ' ', $pageName));
                                        }, $incompletePages);

                                        // Show notification with incomplete pages
                                        Notification::make()
                                            ->warning()
                                            ->title('Información de póliza incompleta')
                                            ->body('Por favor, complete las siguientes secciones antes de continuar: '.implode(', ', $readablePageNames))
                                            ->persistent()
                                            ->send();

                                        return;
                                    }

                                    // Begin transaction for bulk operations
                                    \DB::beginTransaction();

                                    try {
                                        // Get the selected policy types from the CheckBoxList
                                        $selectedPolicyTypes = $get('quote_policy_types') ?? [];

                                        // Check if there are policy types selected
                                        if (empty($selectedPolicyTypes)) {
                                            throw new \Exception('No se han seleccionado tipos de póliza para crear.');
                                        }

                                        $currentPolicy = $record;
                                        $createdPolicies = 0;

                                        // Check if the current policy type is in the selected types
                                        $currentPolicyTypeSelected = in_array($currentPolicy->policy_type->value, $selectedPolicyTypes);

                                        // If current policy type is selected, update its status to Created
                                        if ($currentPolicyTypeSelected) {
                                            $currentPolicy->status = PolicyStatus::Created;
                                            $currentPolicy->save();
                                            $createdPolicies++;
                                        }

                                        // Create new policies for each selected type (except the current one if it's already selected)
                                        foreach ($selectedPolicyTypes as $policyTypeValue) {
                                            // Skip if this is the current policy's type that was already updated
                                            $policyType = PolicyType::from($policyTypeValue);
                                            if ($policyType === $currentPolicy->policy_type && $currentPolicyTypeSelected) {
                                                continue;
                                            }

                                            // Create a new policy with the same data but different type
                                            $newPolicy = $currentPolicy->replicate(['id']);
                                            $newPolicy->policy_type = $policyType;
                                            $newPolicy->status = PolicyStatus::Created;
                                            $newPolicy->policy_number = null; // Generate a new policy number if needed

                                            // Make sure to associate the same contact with the new policy
                                            // This is important because the contact is a belongsTo relationship
                                            $newPolicy->contact_id = $currentPolicy->contact_id;

                                            // Set specific fields for Life policies
                                            if ($policyType === PolicyType::Life) {
                                                $newPolicy->total_family_members = 1;
                                                $newPolicy->total_applicants = 1;
                                                $newPolicy->total_applicants_with_medicaid = 0;
                                            }

                                            // We don't need to set the code manually
                                            // The Policy model's boot() method will automatically generate a unique code
                                            // based on the policy type and existing sequence numbers
                                            $newPolicy->code = null; // Force the model to generate a new code
                                            $newPolicy->save();

                                            // For Life policies, only include the main applicant with 'self' relationship
                                            if ($policyType === PolicyType::Life) {
                                                // Find the main applicant (with 'self' relationship)
                                                $mainApplicant = $currentPolicy->applicants->first(function ($applicant) {
                                                    return $applicant->pivot->relationship_with_policy_owner === FamilyRelationship::Self->value;
                                                });

                                                // If found, attach only the main applicant
                                                if ($mainApplicant) {
                                                    $pivotData = $mainApplicant->pivot->toArray();
                                                    unset($pivotData['id'], $pivotData['policy_id'], $pivotData['created_at'], $pivotData['updated_at']);
                                                    $newPolicy->applicants()->attach($mainApplicant->id, $pivotData);
                                                }
                                            } else {
                                                // For other policy types, copy all applicants
                                                foreach ($currentPolicy->applicants as $applicant) {
                                                    // Get the pivot data
                                                    $pivotData = $applicant->pivot->toArray();
                                                    // Remove keys that shouldn't be copied
                                                    unset($pivotData['id'], $pivotData['policy_id'], $pivotData['created_at'], $pivotData['updated_at']);

                                                    // Attach the applicant to the new policy with the same pivot data
                                                    $newPolicy->applicants()->attach($applicant->id, $pivotData);
                                                }
                                            }

                                            $createdPolicies++;
                                        }

                                        // If no policies were created or updated, show a warning
                                        if ($createdPolicies === 0) {
                                            throw new \Exception('No se ha creado ni actualizado ninguna póliza.');
                                        }

                                        // Commit the transaction
                                        \DB::commit();

                                        // Show success notification with appropriate message
                                        $message = $createdPolicies === 1
                                            ? 'Se ha creado/actualizado 1 póliza correctamente.'
                                            : "Se han creado/actualizado {$createdPolicies} pólizas correctamente.";

                                        Notification::make()
                                            ->success()
                                            ->title('Pólizas procesadas')
                                            ->body($message)
                                            ->send();

                                        // Redirect to the policies index page
                                        $this->redirect(PolicyResource::getUrl('index'));
                                    } catch (\Exception $e) {
                                        // Rollback the transaction in case of error
                                        \DB::rollBack();

                                        // Show error notification
                                        Notification::make()
                                            ->danger()
                                            ->title('Error al procesar pólizas')
                                            ->body($e->getMessage())
                                            ->persistent()
                                            ->send();
                                    }
                                }),
                        ])->columnStart(1)
                            ->alignment('left'),
                    ]),

                Forms\Components\Section::make('Poliza de Vida')
                    ->visible(function (Forms\Get $get) {
                        return in_array(PolicyType::Life->value, $get('quote_policy_types'));
                    })
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
                                    ->formatStateUsing(fn ($state) => number_format($state, 2)),
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
                                    ->formatStateUsing(fn ($state) => number_format($state, 2)),
                                Forms\Components\TextInput::make('life_insurance.applicant.height_feet')
                                    ->label('Altura (pies)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                        $set('life_insurance.applicant.height_cm', number_format($state * 30.48, 2));
                                    })
                                    ->formatStateUsing(fn ($state) => number_format($state, 2)),
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
                                    ->formatStateUsing(fn ($state) => number_format($state, 2)),
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
                                    ->disabled(fn ($get) => ! $get('life_insurance.applicant.has_been_hospitalized')),

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
                                            ->disabled(fn ($get) => ! $get('life_insurance.father.is_alive'))
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
                                            ->disabled(fn ($get) => ! $get('life_insurance.mother.is_alive'))
                                            ->columnSpan(4),
                                        Forms\Components\Toggle::make('life_insurance.family.member_final_disease')
                                            ->label('Familiar con enfermedad')
                                            ->live()
                                            ->columnSpan(2)
                                            ->inline(false),
                                        Forms\Components\Textinput::make('life_insurance.family.member_final_disease_relationship')
                                            ->label('Parentesco')
                                            ->disabled(fn ($get) => ! $get('life_insurance.family.member_final_disease'))
                                            ->columnSpan(4),
                                        Forms\Components\TextInput::make('life_insurance.family.member_final_disease_description')
                                            ->label('Descripción de la enfermedad')
                                            ->disabled(fn ($get) => ! $get('life_insurance.family.member_final_disease'))
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
                                    ->label('Fecha inicio de empleo'),
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
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 2
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->label('Nombre del Beneficiario 2')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.2.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.2.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.2.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 2)
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 3
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->label('Nombre del Beneficiario 3')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.3.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.3.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.3.percentage')
                                    ->label('Asignación')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 3)
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 4
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->label('Nombre del Beneficiario 4')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.4.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.4.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.4.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 4)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 5
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->label('Nombre del Beneficiario 5')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.5.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.5.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.5.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 5)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateBeneficiariesAssignation($set, $get)),

                                // Beneficiario 6
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->label('Nombre del Beneficiario 6')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.beneficiaries.6.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.beneficiaries.6.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.beneficiaries.6.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_beneficiaries') < 6)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
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
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 2
                                Forms\Components\TextInput::make('life_insurance.contingents.2.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->label('Nombre del Contingente 2')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.2.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.2.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.2.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 2)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 3
                                Forms\Components\TextInput::make('life_insurance.contingents.3.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->label('Nombre del Contingente 3')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.3.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.3.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.3.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 3)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 4
                                Forms\Components\TextInput::make('life_insurance.contingents.4.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->label('Nombre del Contingente 4')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.4.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.4.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.4.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 4)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 5
                                Forms\Components\TextInput::make('life_insurance.contingents.5.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->label('Nombre del Contingente 5')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.5.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.5.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.5.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 5)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
                                        $state,
                                        Forms\Set $set,
                                        Forms\Get $get
                                    ) => static::updateContingentsAssignation($set, $get)),

                                // Beneficiario 6
                                Forms\Components\TextInput::make('life_insurance.contingents.6.name')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->label('Nombre del Contingente 6')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('life_insurance.contingents.6.date_of_birth')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Fecha Nacimiento'),
                                Forms\Components\Select::make('life_insurance.contingents.6.relationship')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->label('Parentesco')
                                    ->columnSpan(2)
                                    ->options(FamilyRelationship::class),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.id_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Número ID'),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.phone_number')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Teléfono'),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.email')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->columnSpan(2)
                                    ->label('Correo'),
                                Forms\Components\TextInput::make('life_insurance.contingents.6.percentage')
                                    ->hidden(fn (Forms\Get $get): bool => $get('life_insurance.total_contingents') < 6)
                                    ->label('Asignación')
                                    ->prefix('%')
                                    ->required()
                                    ->numeric()
                                    ->live(onBlur: true)
                                    ->columnSpan(2)
                                    ->afterStateUpdated(fn (
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
