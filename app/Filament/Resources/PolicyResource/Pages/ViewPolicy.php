<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
;use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Filament\Resources\PolicyResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;
use App\Enums\PolicyStatus;
use App\Enums\DocumentStatus;
use App\Enums\UsState;
use App\Enums\PolicyType;
use App\Models\Policy;




class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Miembros';

    protected static ?string $navigationIcon = 'carbon-pedestrian-family';

    public $readonly = true;


    
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if(!isset($data['main_applicant'])) {
            $data['main_applicant'] = [];
        }

        if(!isset($data['contact_information'])) {
            $data['contact_information'] = [];
        }

        $data['contact_information']['first_name'] = $data->contact->first_name ?? null;
        $data['contact_information']['middle_name'] = $data->contact->middle_name ?? null;
        $data['contact_information']['last_name'] = $data->contact->last_name ?? null;
        $data['contact_information']['second_last_name'] = $data->contact->second_last_name ?? null;

        $data['main_applicant']['fullname'] = $data['contact_information']['first_name'] . ' ' . $data['contact_information']['middle_name'] . $data['contact_information']['last_name'] . $data['contact_information']['second_last_name'];

        if ($data['policy_us_state'] === 'KY' ) {
            $data['requires_aca'] = true;
        }
        return $data;


    }

    public  function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Poliza')
                    ->schema([
                        Forms\Components\Select::make('policy_type')
                            ->options(PolicyType::class)
                            ->columnSpan(2)
                            ->label('Tipo'),
                        Forms\Components\Select::make('agent_id')
                            ->relationship('agent', 'name')
                            ->label('Cuenta')
                            ->columnSpan(2),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Asistente')
                            ->columnSpan(2),
                        Forms\Components\Select::make('previous_year_policy_user_id')
                            ->relationship('previousYearPolicyUser', 'name')
                            ->label('Asistente Año Anterior')
                            ->columnSpan(2),
                        

                        Forms\Components\Select::make('insurance_company_id')
                            ->relationship('insuranceCompany', 'name')
                            ->preload()
                            ->label('Aseguradora')
                            ->searchable()
                            ->columnSpan(2),
                        Forms\Components\Select::make('policy_year')
                            ->label('Año')
                            ->options(function() {
                                $startYear = 2018;
                                $endYear = Carbon::now()->addYears(2)->year;
                                $years = [];

                                for ($year = $startYear; $year <= $endYear; $year++) {
                                    $years[$year] = $year;
                                }

                                return $years;
                            })
                            ->default(Carbon::now()->year),
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Inicio')
                            ->columnSpan(2)
                            ->extraInputAttributes([
                                'class' => 'text-center'
                            ]),
                       
                            
                            
                        Forms\Components\Grid::make('')
                            ->schema([
                                Forms\Components\TextInput::make('policy_plan')
                                    ->label('Plan')
                                    ->columnSpan(5),
                                Forms\Components\TextInput::make('policy_total_cost')
                                    ->label('Costo Poliza'),
                                Forms\Components\TextInput::make('policy_total_subsidy')
                                    ->label('Subsidio'),
                                Forms\Components\TextInput::make('premium_amount')
                                    ->label('Prima'),
                                Forms\Components\Select::make('contact_id')
                                    ->label('Cliente')
                                    ->relationship('contact', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('full_name')
                                            ->label('Nombre')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('policy_zipcode')
                                    ->label('Código Postal')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('policy_city')
                                    ->label('Ciudad')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('policy_us_county')
                                    ->label('Condado')
                                    ->columnSpan(1),
                                Forms\Components\Select::make('policy_us_state')
                                    ->label('Estado')
                                    ->live()
                                    ->options(UsState::class)
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('kynect_case_number')
                                    ->label('Caso Kynect')
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('has_existing_kynect_case')
                                    ->inline(false)
                                    ->columnSpan(2)
                                    ->label('Pedir Caso Kynect'),
                                Forms\Components\TextInput::make('total_applicants')
                                    ->label('Aplicantes')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('total_family_members')
                                    ->label('Familiares')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('estimated_household_income')
                                    ->label('Ingresos Estimados')
                                    ->columnSpan(2),
                                ])->columns(['sm' => 4, 'md' => 8, 'lg' => 8, 'xl' => 8])->columnSpanFull(),

                        Forms\Components\Fieldset::make()
                            ->schema([
                                
                                Forms\Components\Select::make('status')
                                    ->label('Estatus')
                                    ->columnSpan(2)
                                    ->options(PolicyStatus::class),
                                Forms\Components\Select::make('document_status')
                                    ->columnSpan(2)
                                    ->label('Documentos')
                                    ->disabled()
                                    ->default(DocumentStatus::ToAdd)
                                    ->options(DocumentStatus::class),
                                Forms\Components\Toggle::make('client_notified')
                                    ->inline(false)
                                    ->label('Notificado'),
                                Forms\Components\Toggle::make('autopay')
                                    ->inline(false)
                                    ->label('Cotizacion'),
                                Forms\Components\Toggle::make('initial_paid')
                                    ->inline(false)
                                    ->label('Pagada'),
                                Forms\Components\Toggle::make('aca')
                                    ->inline(false)
                                    ->label('ACA')
                                    ->disabled(fn (Forms\Get $get): bool => $get ('policy_us_state') != UsState::KENTUCKY->value),
                                Forms\Components\Toggle::make('is_initial_verification_complete')
                                    ->inline(false)
                                    ->live()
                                    ->columnSpan(2)
                                    ->label('Verificacion Inicial'),
                                Forms\Components\Select::make('initial_verification_performed_by')
                                    ->relationship('initialVerificationPerformedBy', 'name')
                                    ->label('Verificado Por')
                                    ->disabled(fn (Get $get) => $get('is_initial_verification_complete') != true)
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('initial_verification_date')
                                    ->label('Fecha Verificacion')
                                    ->disabled(fn (Get $get) => $get('is_initial_verification_complete') != true)
                                    ->columnSpan(3),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Observaciones')
                                    ->rows(6)
                                    ->columnSpanFull(),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('add_note')
                                        ->label('Agregar Nota')
                                        ->color('info')
                                        ->modalHeading('Agregar Nota')
                                        ->visible(fn (?Policy $record) => $record && $record->exists)
                                        ->form([
                                            Forms\Components\Textarea::make('note')
                                                ->required()
                                                ->rows(3)
                                                ->label('Nota'),
                                        ])
                                        ->modalSubmitActionLabel('Agregar')
                                        ->action(function (Policy $record, array $data, Set $set): void {
                                            $note = Carbon::now()->toDateTimeString() . ' - ' . auth()->user()->name . ":\n" . $data['note'] . "\n\n" ;
                                            $record->notes = $record->notes . $note;
                                            $record->save();

                                            // Refresh the notes field with the updated value
                                            $set('notes', $record->notes);
                                        }),
                                    ])
                                    ->alignEnd()
                                    ->columnSpanFull(),
                                ])
                            ->columns([ 'md' => 8, 'lg' => 8 ])
                            ->columnSpanFull(),
                        ])->columns(['md' => 8, 'lg' => 8]),
            ]);
    }

}
