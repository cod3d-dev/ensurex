<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Enums\UsState;
use App\Filament\Resources\PolicyResource;
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
    
    public static string|\Filament\Support\Enums\Alignment $formActionsAlignment = 'end';
    
    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label(function () {
                // Check if all pages have been completed
                $record = $this->getRecord();
                $isCompleted = $record->areRequiredPagesCompleted();
                
                // Return 'Siguiente' if not completed, otherwise 'Guardar'
                return $isCompleted ? 'Guardar' : 'Siguiente';
            })
            ->icon(fn () => $this->getRecord()->areRequiredPagesCompleted() ? '' : 'heroicon-o-arrow-right')
            ->color(function () {
                $record = $this->getRecord();
                return $record->areRequiredPagesCompleted() ? 'primary' : 'success';
            });    
    }

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

    protected function afterSave(): void
    {
        // Get the policy model
        $policy = $this->record;

        // Count the total family members (all applicants in the pivot table)
        $totalFamilyMembers = $policy->policyApplicants()->count();

        // Count applicants where is_covered_by_policy is true
        $totalCoveredApplicants = $policy->policyApplicants()
            ->where('is_covered_by_policy', true)
            ->count();

        // Count applicants where medicaid_client is true
        $totalMedicaidApplicants = $policy->policyApplicants()
            ->where('medicaid_client', true)
            ->count();

        // Update the policy with the new counts
        $policy->update([
            'total_family_members' => $totalFamilyMembers,
            'total_applicants' => $totalCoveredApplicants,
            'total_applicants_with_medicaid' => $totalMedicaidApplicants,
        ]);

        // Mark this page as completed
        $policy->markPageCompleted('edit_policy_applicants_data');
        
        // If all required pages are completed, redirect to the completion page
        if ($policy->areRequiredPagesCompleted()) {
            $this->redirect(PolicyResource::getUrl('edit-complete', ['record' => $policy]));
            return;
        }
        
        // Get the next uncompleted page and redirect to it
        $incompletePages = $policy->getIncompletePages();
        if (!empty($incompletePages)) {
            $nextPage = reset($incompletePages); // Get the first incomplete page
            
            // Map page names to their respective routes
            $pageRoutes = [
                'edit_policy' => 'edit',
                'edit_policy_contact' => 'edit-contact',
                'edit_policy_applicants' => 'edit-applicants',
                'edit_policy_applicants_data' => 'edit-applicants-data',
                'edit_policy_income' => 'edit-income',
                'edit_policy_payments' => 'payments',
            ];
            
            if (isset($pageRoutes[$nextPage])) {
                $this->redirect(PolicyResource::getUrl($pageRoutes[$nextPage], ['record' => $policy]));
            }
        }
    }

    public function form(Form $form): Form
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
                            ->columns(['sm' => 12, 'md' => 6, 'lg' => 4])
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
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, ?bool $state): void {
                                                        if ($state) {
                                                            $set('medicaid_client', false);
                                                        }
                                                    })
                                                    ->columnSpan(1)
                                                    ->label('¿Aplicante?')
                                                    ->required(),
                                                Forms\Components\Toggle::make('medicaid_client')
                                                    ->inline(false)
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, ?bool $state): void {
                                                        if ($state) {
                                                            $set('is_covered_by_policy', false);
                                                        }
                                                    })
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
                                                            ->required(fn (Get $get) => $get('../relationship_with_policy_owner') == FamilyRelationship::Self->value)
                                                            ->tel()
                                                            ->columnSpan(2),
                                                        Forms\Components\TextInput::make('phone2')
                                                            ->label('Teléfono 2')
                                                            ->tel()
                                                            ->columnSpan(2),
                                                        Forms\Components\TextInput::make('email_address')
                                                            ->columnSpan(3)
                                                            ->required(fn (Get $get) => $get('../relationship_with_policy_owner') === FamilyRelationship::Self->value)
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
                                                                    ->required()
                                                                    ->columnSpan(3)
                                                                    ->options(ImmigrationStatus::class)
                                                                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                                                        if ($state) {
                                                                            $set('immigration_status_category', null);
                                                                        }
                                                                    })
                                                                    ->live(),
                                                                Forms\Components\TextInput::make('immigration_status_category')
                                                                    ->label('Descripción')
                                                                    ->required(fn (Get $get) => $get('immigration_status') == ImmigrationStatus::Other->value)
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

                                                            ])->columns(['sm' => 7, 'md' => 7, 'lg' => 7]),
                                                    ])->columns(['sm' => 9, 'md' => 9, 'lg' => 9]),

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
                                            ->columns(['sm' => 7, 'md' => 7, 'lg' => 7])
                                            ->columnSpanFull(),

                                        //

                                        //

                                    ])->columns(['sm' => 4, 'md' => 4, 'lg' => 4]),

                            ])
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->collapsible(false)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(['sm' => 3, 'md' => 3, 'lg' => 3]),
                    ]),
            ]);
    }
}
