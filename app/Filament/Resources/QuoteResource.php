<?php

namespace App\Filament\Resources;

use App\Enums\FamilyRelationship;
use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Models\Quote;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Wizard;
//use Filament\Forms\FormEvents;
use App\Filament\Resources\QuoteResource\Actions\ConvertToPolicy;
use App\Enums\QuoteStatus;
//use Awcodes\TableRepeater\Components\TableRepeater;
//use Awcodes\TableRepeater\Header;
//use App\Actions\ResetStars;
use Filament\Forms\Components\Actions\Action;
//use App\Actions\Star;
use Filament\Forms\Components\Actions;
use App\Enums\Gender;
use App\Enums\PolicyType;
use App\Enums\UsState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Filament\Tables\Enums\FiltersLayout;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Cotizaciones';
    protected static ?string $modelLabel = 'Cotización';
    protected static ?string $pluralModelLabel = 'Cotizaciones';
    protected static ?string $navigationGroup = 'Polizas';
    protected static ?int $navigationSort = 1;

//    public static function shouldRegisterNavigation(): bool
//    {
//        return true;
//    }

// public static function getGloballySearchableAttributes(): array
//     {
//         return ['contact.first_name', 'contact.middle_name', 'contact.last_name', 'contact.second_last_name'];
//     }

    // public static function getGlobalSearchResultDetails(Model $record): array
    // {
    //     return [
    //         'Cliente' => $record->contact->full_name,
    //         'Tipo' => $record->policyType->name ?? null,
    //         'Año' => $record->year,
    //         // Return Pagado if $record->initial_paid is true
    //         'Estatus' => $record->status->getLabel(),
    //     ];
    // }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make()
                    ->schema([
                        Wizard\Step::make('Información Básica')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\Select::make('agent_id')
                                            ->relationship('Agent', 'name')
                                            ->required()
                                            ->label('Cuenta')
                                            ->default(1)
                                            ->columnSpan(2),
                                        Forms\Components\Select::make('user_id')
                                            ->relationship('user', 'name')
                                            ->required()
                                            ->label('Asistente')
                                            ->searchable()
                                            ->preload()
                                            ->default(auth()->user()->id)
                                            ->columnSpan(2),
                                        Forms\Components\Select::make('policy_type')
                                            ->options(PolicyType::class)
                                            ->required()
                                            ->label('Tipo')
                                            ->default(PolicyType::Health)
                                            ->columnSpan(2),
                                        Forms\Components\Select::make('year')
                                            ->required()
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
                                            ->default(Carbon::now()->year)
                                            ->columnSpan(1),
                                        Forms\Components\Hidden::make('policy_id')
                                            ->dehydrated(true),
                                        Forms\Components\Select::make('status')
                                            ->label('Estatus')
                                            ->options(QuoteStatus::class)
                                            ->default(QuoteStatus::Pending)
                                            ->required()
                                            ->columnSpan(2)
                                            ->suffixAction(
                                                Action::make('poliza')
                                                    ->icon('iconoir-privacy-policy')
                                                    ->label('Ver Póliza')
                                                    ->tooltip('Ver póliza asociada')
                                                    ->visible(fn (Forms\Get $get) => $get('policy_id') !== null)
                                                    ->url(function (Forms\Get $get) {
                                                        $policyId = $get('policy_id');

                                                        if ($policyId) {
                                                            return PolicyResource::getUrl('view', ['record' => $policyId]);
                                                        }
                                                        return '';
                                                    })
                                                    ->openUrlInNewTab()),
                                    ])->columns(9),


                                Section::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('create_new_client')
                                            ->label('Crear Nuevo Cliente')
                                            ->live()
                                            ->default(true)
                                            ->inline(false),
                                        Forms\Components\Select::make('contact_id')
                                            ->relationship(
                                                'contact',
                                                'first_name',
                                                fn(Builder $query
                                                ) => $query->orderBy('first_name')->orderBy('last_name')
                                            )
                                            ->getOptionLabelFromRecordUsing(fn(Contact $record) => $record->full_name)
                                            ->disabled(fn(Forms\Get $get): bool => $get('create_new_client'))
                                            ->dehydrated(true)
                                            ->required(fn(Forms\Get $get): bool => !$get('create_new_client'))
                                            ->label('Cliente')
                                            ->searchable(['first_name', 'last_name', 'middle_name', 'second_last_name'])
                                            ->preload()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $contact = Contact::find($state);
                                                    if ($contact) {
                                                        $set('contact_information.first_name', $contact->first_name);
                                                        $set('contact_information.last_name', $contact->last_name);
                                                        $set('contact_information.middle_name', $contact->middle_name);
                                                        $set('contact_information.second_last_name',
                                                            $contact->second_last_name);
                                                        $set('contact_information.date_of_birth',
                                                            $contact->date_of_birth);
                                                        $set('contact_information.gender', $contact->gender);
                                                        $set('contact_information.phone', $contact->phone);
                                                        $set('contact_information.email_address',
                                                            $contact->email_address);
                                                        $set('contact_information.zip_code',
                                                            $contact->zip_code);
                                                        $set('contact_information.is_tobacco_user',
                                                            $contact->is_tobacco_user);
                                                        $set('contact_information.is_pregnant', $contact->is_pregnant);
                                                        $set('contact_information.kommo_id', $contact->kommo_id);
                                                        $set('contact_information.is_eligible_for_coverage',
                                                            $contact->is_eligible_for_coverage);

                                                    }
                                                }
                                            })
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('contact_information.first_name')
                                            ->label('Primer Nombre')
                                            ->required(),
                                        Forms\Components\TextInput::make('contact_information.middle_name')
                                            ->label('Segundo Nombre'),
                                        Forms\Components\TextInput::make('contact_information.last_name')
                                            ->label('Primer Apellido')
                                            ->required(),
                                        Forms\Components\TextInput::make('contact_information.second_last_name')
                                            ->label('Segundo Apellido'),
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\DatePicker::make('contact_information.date_of_birth')
                                                    ->label('Fecha de Nacimiento')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateHydrated(function ($state, Forms\Set $set) {
                                                        if ($state) {
                                                            $birthDate = \Carbon\Carbon::parse($state);
                                                            $age = $birthDate->age;
                                                            $set('contact_information.age', $age);
                                                        }
                                                    })
                                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                        if ($state) {
                                                            $birthDate = \Carbon\Carbon::parse($state);
                                                            $age = $birthDate->age;
                                                            $set('contact_information.age', $age);
                                                        }
                                                    })
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('contact_information.age')
                                                    ->label('Edad')
                                                    ->disabled(),
                                                Forms\Components\Select::make('contact_information.gender')
                                                    ->label('Género')
                                                    ->placeholder('Genero')
                                                    ->options(Gender::class)
                                                    ->required()
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('contact_information.phone')
                                                    ->label('Teléfono')
                                                    ->tel()
                                                    ->required()
                                                    ->columnSpan(2),
                                                Forms\Components\TextInput::make('contact_information.kommo_id')
                                                    ->label('Kommo ID')
                                                    ->columnSpan(2),
                                            ])
                                            ->columns(9)
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('contact_information.zip_code')
                                                    ->label('Código Postal')
                                                    ->required(),
                                                Forms\Components\TextInput::make('contact_information.county')
                                                    ->label('Condado')
                                                    ->required(),
                                                Forms\Components\TextInput::make('contact_information.city')
                                                    ->label('Ciudad')
                                                    ->required(),
                                                Forms\Components\Select::make('contact_information.state')
                                                    ->label('Estado')
                                                    ->placeholder('Estado')
                                                    ->options(UsState::class)
                                                    ->searchable()
                                                    ->required(),
                                                Forms\Components\TextInput::make('contact_information.email_address')
                                                    ->label('Email')
                                                    ->email()
                                                    ->columnSpan(2),
                                            ])
                                            ->columns(6)
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('contact_information.is_tobacco_user')
                                            ->label('¿Usa Tabaco?'),
                                        Forms\Components\Toggle::make('contact_information.is_pregnant')
                                            ->label('¿Embarazada?'),
                                        Forms\Components\Toggle::make('contact_information.is_eligible_for_coverage')
                                            ->label('¿Elegible para Medicare?'),


                                    ])
                                    ->columns(4),


                            ])->columns(2),

                        Wizard\Step::make('Aplicantes')
                            ->schema([
                                Section::make('Aplicantes')
                                    ->schema([
                                        Forms\Components\TextInput::make('total_family_members')
                                            ->numeric()
                                            ->label('Total Miembros Familiares')
                                            ->required()
                                            ->default(1)
                                            ->live()
                                            ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                                $kinectKPL = \App\Models\KynectFPL::threshold(2024, (int) $state);
                                                $set('../data.kynect_fpl_threshold', $kinectKPL * 12);
                                            }),
                                        Forms\Components\TextInput::make('total_applicants')
                                            ->numeric()
                                            ->label('Total Solicitantes')
                                            ->required()
                                            ->default(1)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $additional_applicants = $get('additional_applicants') ?? [];
                                                $applicants_count = count($additional_applicants);

                                                // If we need more rows than we currently have
                                                if ($state > $applicants_count) {
                                                    // Keep existing members
//                                                    $newFamilyMembers = $familyMembers;

                                                    // Add new empty rows
                                                    for ($i = $applicants_count; $i < $state - 1; $i++) {
                                                        $additional_applicants[] = [
                                                            'relationship' => '',
                                                            'age' => '',
                                                            'pregnant' => false,
                                                            'tobacco_user' => false,
                                                            'is_eligible_for_coverage' => false,
                                                            'income_per_hour' => '',
                                                            'hours_per_week' => '',
                                                            'income_per_extra_hour' => '',
                                                            'extra_hours_per_week' => '',
                                                            'weeks_per_year' => '',
                                                            'yearly_income' => '',
                                                            'is_self_employed' => false,
                                                            'self_employed_yearly_income' => '',
                                                        ];
                                                    }
                                                } else {
                                                    // If we need fewer rows, just keep the first $state rows
                                                    $additional_applicants = array_slice($additional_applicants, 0,
                                                        $state - 1);
                                                }

                                                $set('additional_applicants', $additional_applicants);
                                            }),
                                        Forms\Components\TextInput::make('estimated_household_income')
                                            ->numeric()
                                            ->label('Ingreso Familiar Estimado')
                                            ->prefix('$'),
                                        Forms\Components\TextInput::make('kynect_fpl_threshold')
                                            ->label('Ingresos Requeridos Kynect')
                                            ->disabled()
                                            ->prefix('$')
                                            ->live()
                                            ->formatStateUsing(function ($state, $get) {
                                                $memberCount = $get('../../total_family_members') ?? 1;
                                                $kinectKPL = floatval(\App\Models\KynectFPL::threshold(2024,
                                                    $memberCount));
                                                return $kinectKPL * 12;
                                            })
                                            ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                                $memberCount = $get('../../total_family_members') ?? 1;
                                                $kinectKPL = floatval(\App\Models\KynectFPL::threshold(2024,
                                                    $memberCount));
                                                $set('kynect_fpl_threshold', $kinectKPL * 12);
                                            }),
                                    ])->columns(4),


                                Section::make('Ingresos Familiares')
                                    ->schema([
                                        Section::make('Aplicante Principal')
                                            ->schema([
                                                Forms\Components\Grid::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('main_applicant.income_per_hour')
                                                            ->numeric()
                                                            ->label('Hora $')
                                                            ->live(onBlur: true)
                                                            ->disabled(fn(Forms\Get $get
                                                            ): bool => $get('main_applicant.is_self_employed'))
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('main', $state, $set,
                                                                $get)),
                                                        Forms\Components\TextInput::make('main_applicant.hours_per_week')
                                                            ->numeric()
                                                            ->label('Horas/Semana')
                                                            ->live(onBlur: true)
                                                            ->disabled(fn(Forms\Get $get
                                                            ): bool => $get('main_applicant.is_self_employed'))
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('main', $state, $set,
                                                                $get)),
                                                        Forms\Components\TextInput::make('main_applicant.income_per_extra_hour')
                                                            ->numeric()
                                                            ->label('Hora Extra $')
                                                            ->live(onBlur: true)
                                                            ->disabled(fn(Forms\Get $get
                                                            ): bool => $get('main_applicant.is_self_employed'))
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('main', $state, $set,
                                                                $get)),
                                                        Forms\Components\TextInput::make('main_applicant.extra_hours_per_week')
                                                            ->numeric()
                                                            ->label('Extra/Semana')
                                                            ->live(onBlur: true)
                                                            ->disabled(fn(Forms\Get $get
                                                            ): bool => $get('main_applicant.is_self_employed'))
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('main', $state, $set,
                                                                $get)),
                                                        Forms\Components\TextInput::make('main_applicant.weeks_per_year')
                                                            ->numeric()
                                                            ->label('Semanas por Año')
                                                            ->live(onBlur: true)
                                                            ->disabled(fn(Forms\Get $get
                                                            ): bool => $get('main_applicant.is_self_employed'))
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('main', $state, $set,
                                                                $get)),
                                                        Forms\Components\TextInput::make('main_applicant.yearly_income')
                                                            ->numeric()
                                                            ->label('Ingreso Anual')
                                                            ->disabled(),


                                                        Forms\Components\Toggle::make('main_applicant.is_eligible_for_coverage')
                                                            ->label('¿Elegible Medicare?')
                                                            ->inline(false)
                                                            ->default(false),
                                                        Forms\Components\Toggle::make('main_applicant.is_self_employed')
                                                            ->label('¿Self Employeed?')
                                                            ->inline(false)
                                                            ->live()
                                                            ->columnStart(4)
                                                            ->afterStateHydrated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('main', $state, $set,
                                                                $get))
                                                            ->afterStateUpdated(function (
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) {
                                                                static::lockHourlyIncome('main', $state, $set, $get);
                                                                static::calculateYearlyIncome('main', $state, $set,
                                                                    $get);
                                                            }),
                                                        Forms\Components\TextInput::make('main_applicant.self_employed_profession')
                                                            ->label('Profesión')
                                                            ->disabled(fn(Forms\Get $get
                                                            ): bool => !$get('main_applicant.is_self_employed')),
                                                        Forms\Components\TextInput::make('main_applicant.self_employed_yearly_income')
                                                            ->numeric()
                                                            ->label('Ingreso Anual')
                                                            ->live(onBlur: true)
                                                            ->columnStart(6)
                                                            ->disabled(fn(Forms\Get $get
                                                            ): bool => !$get('main_applicant.is_self_employed'))
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('main', $state, $set,
                                                                $get)),
                                                    ])->columns(6),

                                            ]),

                                        Section::make('Aplicantes Adicionales')
                                            ->schema([
                                                Forms\Components\Repeater::make('additional_applicants')
                                                    ->hiddenLabel()
                                                    ->addable(false)
                                                    ->defaultItems(0)
                                                    ->schema([
                                                        Forms\Components\Select::make('relationship')
                                                            ->label('Parentesco')
                                                            ->options(FamilyRelationship::class)
                                                            ->required(),
                                                        Forms\Components\TextInput::make('age')
                                                            ->label('Edad')
                                                            ->numeric()
                                                            ->required(),
                                                        Forms\Components\Select::make('gender')
                                                            ->label('Género')
                                                            ->options(Gender::class)
                                                            ->required(),
                                                        Forms\Components\Toggle::make('is_pregnant')
                                                            ->label('¿Embarazada?')
                                                            ->inline(false)
                                                            ->default(false),
                                                        Forms\Components\Toggle::make('is_tobacco_user')
                                                            ->label('¿Fuma?')
                                                            ->inline(false)
                                                            ->default(false),
                                                        Forms\Components\Toggle::make('is_eligible_for_coverage')
                                                            ->label('¿Elegible Medicare?')
                                                            ->inline(false)
                                                            ->default(false),
                                                        Forms\Components\TextInput::make('income_per_hour')
                                                            ->numeric()
                                                            ->label('Hora $')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('additional', $state,
                                                                $set, $get)),
                                                        Forms\Components\TextInput::make('hours_per_week')
                                                            ->numeric()
                                                            ->label('Horas/Semana')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('additional', $state,
                                                                $set, $get)),
                                                        Forms\Components\TextInput::make('income_per_extra_hour')
                                                            ->numeric()
                                                            ->label('Hora Extra $')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('additional', $state,
                                                                $set, $get)),
                                                        Forms\Components\TextInput::make('extra_hours_per_week')
                                                            ->numeric()
                                                            ->label('Extra/Semana')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('additional', $state,
                                                                $set, $get)),
                                                        Forms\Components\TextInput::make('weeks_per_year')
                                                            ->numeric()
                                                            ->label('Semanas por Año')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('additional', $state,
                                                                $set, $get)),
                                                        Forms\Components\TextInput::make('yearly_income')
                                                            ->numeric()
                                                            ->label('Ingreso Anual')
                                                            ->disabled(),

                                                        Forms\Components\Toggle::make('is_self_employed')
                                                            ->label('¿Self Employeed?')
                                                            ->inline(false)
                                                            ->live(onBlur: true)
                                                            ->columnStart(5)
                                                            ->afterStateHydrated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('applicant', $state,
                                                                $set, $get))
                                                            ->afterStateUpdated(function (
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) {
                                                                static::lockHourlyIncome('additional', $state, $set,
                                                                    $get);
                                                                static::calculateYearlyIncome('additional', $state,
                                                                    $set, $get);
                                                            }),
                                                        Forms\Components\TextInput::make('self_employed_yearly_income')
                                                            ->numeric()
                                                            ->live(onBlur: true)
                                                            ->label('Ingreso Anual')
                                                            ->columnStart(6)
                                                            ->afterStateUpdated(fn(
                                                                $state,
                                                                Forms\Set $set,
                                                                Forms\Get $get
                                                            ) => static::calculateYearlyIncome('additional', $state,
                                                                $set, $get)),
                                                    ])->columns(6),
                                            ]),
                                    ])->columns(6),

                            ])->columns(3),

                        Wizard\Step::make('Otros')
                            ->schema([
                                Split::make([
                                    Section::make([
                                        Forms\Components\TextInput::make('preferred_doctor')
                                            ->label('Doctor Preferido'),
                                        Forms\Components\Repeater::make('prescription_drugs')
                                            ->label('')
                                            ->defaultItems(0)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nombre del Medicamento')
                                                    ->required(),
                                                Forms\Components\TextInput::make('dosage')
                                                    ->label('Dosis')
                                                    ->required(),
                                                Forms\Components\Select::make('applicant')
                                                    ->label('Aplicante')
                                                    ->options(function (Forms\Get $get) {
                                                        $options = [];

                                                        // Add main applicant
                                                        $mainApplicant = 'Principal - ' .
                                                            ($get('../../contact_information.gender') === 'male' ? 'Masculino' : 'Femenino') .
                                                            ' - ' . $get('../../contact_information.age') . ' años';
                                                        $options[$mainApplicant] = $mainApplicant;

                                                        // Add additional applicants
                                                        $additionalApplicants = $get('../../additional_applicants') ?? [];
                                                        foreach ($additionalApplicants as $applicant) {
                                                            $formattedName = $applicant['relationship'] . ' - ' .
                                                                ($applicant['gender'] === 'male' ? 'Masculino' : 'Femenino') . ' - ' .
                                                                $applicant['age'] . ' años';
                                                            $options[$formattedName] = $formattedName;
                                                        }

                                                        return $options;
                                                    })
                                                    ->required()
                                                    ->live(),
                                                Forms\Components\Select::make('frequency')
                                                    ->label('Suministro (Meses)')
                                                    ->options(
                                                        range(1, 12),
                                                    )
                                                    ->required(),
                                                Forms\Components\Textarea::make('notes')
                                                    ->label('Notas')
                                                    ->rows(2),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('Agregar Medicamento')
                                            ->deleteAction(
                                                fn(Forms\Components\Actions\Action $action
                                                ) => $action->label('Eliminar Medicamento')
                                            )
                                    ]),
                                    Section::make([
                                        // Add a section to view existing documents and add new ones

                                    ]),
                                    Section::make([
                                        // Add a section to view existing documents and add new ones

                                        Actions::make([
                                            Action::make('Health Sherpa')
                                                ->icon('heroicon-m-star')
                                                ->url('https://www.healthsherpa.com/shopping?_agent_id=nil&carrier_id=nil&source=agent-home'),
                                            Action::make('Kommo')
                                                ->icon('heroicon-m-x-mark')
                                                ->color('success')
                                                ->url(fn(Forms\Get $get
                                                ) => 'https://ghercys.kommo.com/leads/detail/'.$get('contact_information.kommo_id')),
                                        ])
                                    ])->grow(false)
                                ])
                                ,
                            ])->columns(1),


                    ])->columnSpanFull(),


            ])
            ->statePath('data')
            ->model(Quote::class);
    }

    protected static function calculateYearlyIncome($applicant, $state, Forms\Set $set, Forms\Get $get): void
    {

        $prefix = $applicant === 'main' ? 'main_applicant.' : '';

        $incomePerHour = floatval($get($prefix.'income_per_hour') ?? 0);
        $hoursPerWeek = floatval($get($prefix.'hours_per_week') ?? 0);
        $incomePerExtraHour = floatval($get($prefix.'income_per_extra_hour') ?? 0);
        $extraHoursPerWeek = floatval($get($prefix.'extra_hours_per_week') ?? 0);
        $weeksPerYear = floatval($get($prefix.'weeks_per_year') ?? 0);

        $yearlyIncome = ($incomePerHour * $hoursPerWeek + $incomePerExtraHour * $extraHoursPerWeek) * $weeksPerYear;

        $set($prefix.'yearly_income', round($yearlyIncome, 2));

        self::updateYearlyIncome($set, $get);
    }

    protected static function lockHourlyIncome($applicant, $state, Forms\Set $set, Forms\Get $get): void
    {
        $prefix = $applicant === 'main' ? 'main_applicant.' : '';

        $set($prefix.'income_per_hour', '');
        $set($prefix.'hours_per_week', '');
        $set($prefix.'income_per_extra_hour', '');
        $set($prefix.'extra_hours_per_week', '');
        $set($prefix.'weeks_per_year', '');
        $set($prefix.'yearly_income', '');
        $set($prefix.'self_employed_yearly_income', '');
    }

    protected static function updateYearlyIncome(Forms\Set $set, Forms\Get $get): void
    {


        // check if main applicant self employed is null
        $mainApplicantSelfEmployed = $get('../../main_applicant.is_self_employed');


        if ($mainApplicantSelfEmployed === null) {
            $mainApplicantSelfEmployed = $get('main_applicant.is_self_employed');

            if ($mainApplicantSelfEmployed) {
                $mainApplicantYearlyIncome = floatval($get('main_applicant.self_employed_yearly_income') ?? 0);
            } else {
                $mainApplicantYearlyIncome = floatval($get('main_applicant.yearly_income') ?? 0);
            }
        } else {
            if ($mainApplicantSelfEmployed) {
                $mainApplicantYearlyIncome = floatval($get('../../main_applicant.self_employed_yearly_income') ?? 0);
            } else {
                $mainApplicantYearlyIncome = floatval($get('../../main_applicant.yearly_income') ?? 0);
            }
        }


        $additionalApplicants = $get('../../additional_applicants') ?? [];
        if (empty($additionalApplicants)) {
            $additionalApplicants = $get('additional_applicants') ?? [];
        }

        $AllApplicantsYearlyIncome = 0;

        foreach ($additionalApplicants as $index => $additionalApplicant) {

            $applicantYearlyIncome = 0;
            if ($additionalApplicant['is_self_employed']) {
                $applicantYearlyIncome = floatval($additionalApplicant['self_employed_yearly_income'] ?? 0);
            } else {
                $incomePerHour = floatval($additionalApplicant['income_per_hour'] ?? 0);
                $hoursPerWeek = floatval($additionalApplicant['hours_per_week'] ?? 0);
                $incomePerExtraHour = floatval($additionalApplicant['income_per_extra_hour'] ?? 0);
                $extraHoursPerWeek = floatval($additionalApplicant['extra_hours_per_week'] ?? 0);
                $weeksPerYear = floatval($additionalApplicant['weeks_per_year'] ?? 0);

                $applicantYearlyIncome = ($incomePerHour * $hoursPerWeek + $incomePerExtraHour * $extraHoursPerWeek) * $weeksPerYear;
            }

            $AllApplicantsYearlyIncome += $applicantYearlyIncome;


        }


        $totalYearlyIncome = $mainApplicantYearlyIncome + $AllApplicantsYearlyIncome;

        $set('../../estimated_household_income', $totalYearlyIncome);
        $set('estimated_household_income', $totalYearlyIncome);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Cuenta')
                    ->badge()
                    ->formatStateUsing(fn( string $state): string => Str::acronym($state))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Agente')
                    ->sortable()
                    ->searchable(),
                    // Tables\Columns\TextColumn::make('contact.full_name')
                    // ->label('Cliente')
                    // ->searchable(query: function (Builder $query, string $search): Builder {
                    //     return $query->where(function (Builder $query) use ($search): Builder {
                    //         // Search in contact fields
                    //         $query->whereHas('contact', function (Builder $query) use ($search): Builder {
                    //             return $query->where('first_name', 'like', "%{$search}%")
                    //                 ->orWhere('middle_name', 'like', "%{$search}%")
                    //                 ->orWhere('last_name', 'like', "%{$search}%")
                    //                 ->orWhere('second_last_name', 'like', "%{$search}%");
                    //         });
                    //         return $query;
                    //     });
                    // })
                    // ->sortable(query: function (Builder $query, string $direction): Builder {
                    //     return $query
                    //         ->join('contacts', 'quotes.contact_id', '=', 'contacts.id')
                    //         ->orderBy('contacts.last_name', $direction)
                    //         ->orderBy('contacts.first_name', $direction)
                    //         ->select('quotes.*');
                    // })
                    // ->description(fn($record) => 'Applicantes: ' . $record->total_applicants),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Cliente')
                    ->sortable(['first_name', 'last_name'])
                    ->searchable(['first_name', 'last_name', 'middle_name', 'second_last_name'])
                    ->description(fn($record) => 'Applicantes: ' . $record->total_applicants),
                Tables\Columns\TextColumn::make('contact_information.state')
                    ->label('Estado')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('m/d/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->sortable(),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                   ->label('Usuario')
                   ->relationship('user', 'name')
                   ->default(auth()->user()->id),
                Tables\Filters\SelectFilter::make('agent.name')
                   ->label('Agente')
                   ->relationship('agent', 'name'),
                Tables\Filters\SelectFilter::make('year')
                   ->label('Año Efectivo')
                   ->options(function() {
                    $startYear = 2018;
                    $endYear = Carbon::now()->addYears(2)->year;
                    $years = [];
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $years[$year] = $year;
                    }
                    return $years;
                }),
                Tables\Filters\SelectFilter::make('state_province')
                    ->label('Estado')
                    ->options(UsState::class),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options(QuoteStatus::class),
                Tables\Filters\SelectFilter::make('created_week')
                    ->label('Semana de Creación')
                    ->options(function() {
                        $options = [];
                        $currentWeek = Carbon::now()->startOfWeek();
                        
                        // Current week
                        $weekStart = $currentWeek->copy()->format('Y-m-d');
                        $weekEnd = $currentWeek->copy()->endOfWeek()->format('Y-m-d');
                        $options["current"] = "Semana Actual ({$weekStart} - {$weekEnd})";
                        
                        // Past 3 weeks
                        for ($i = 1; $i <= 3; $i++) {
                            $weekStart = $currentWeek->copy()->subWeeks($i)->format('Y-m-d');
                            $weekEnd = $currentWeek->copy()->subWeeks($i)->endOfWeek()->format('Y-m-d');
                            $options["week_{$i}"] = "Hace {$i} semana" . ($i > 1 ? 's' : '') . " ({$weekStart} - {$weekEnd})";
                        }
                        
                        return $options;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $currentWeek = Carbon::now()->startOfWeek();
                        
                        return match ($data['value']) {
                            'current' => $query->whereBetween('created_at', [
                                $currentWeek->copy()->startOfWeek(),
                                $currentWeek->copy()->endOfWeek(),
                            ]),
                            'week_1' => $query->whereBetween('created_at', [
                                $currentWeek->copy()->subWeeks(1)->startOfWeek(),
                                $currentWeek->copy()->subWeeks(1)->endOfWeek(),
                            ]),
                            'week_2' => $query->whereBetween('created_at', [
                                $currentWeek->copy()->subWeeks(2)->startOfWeek(),
                                $currentWeek->copy()->subWeeks(2)->endOfWeek(),
                            ]),
                            'week_3' => $query->whereBetween('created_at', [
                                $currentWeek->copy()->subWeeks(3)->startOfWeek(),
                                $currentWeek->copy()->subWeeks(3)->endOfWeek(),
                            ]),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['value']) {
                            return null;
                        }
                        
                        return match ($data['value']) {
                            'current' => 'Semana Actual',
                            'week_1' => 'Hace 1 semana',
                            'week_2' => 'Hace 2 semanas',
                            'week_3' => 'Hace 3 semanas',
                            default => null,
                        };
                    }),
                Tables\Filters\SelectFilter::make('created_month')
                    ->label('Mes de Creación')
                    ->options(function() {
                        $options = [];
                        $currentMonth = Carbon::now();
                        
                        // Current month
                        $options["current"] = "Mes Actual (" . $currentMonth->format('F Y') . ")";
                        
                        // Past 6 months
                        for ($i = 1; $i <= 6; $i++) {
                            $monthDate = $currentMonth->copy()->subMonths($i);
                            $options["month_{$i}"] = $monthDate->format('F Y');
                        }
                        
                        return $options;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value'])) {
                            return $query;
                        }
                        
                        $currentMonth = Carbon::now();
                        
                        return match ($data['value']) {
                            'current' => $query->whereMonth('created_at', $currentMonth->month)
                                              ->whereYear('created_at', $currentMonth->year),
                            'month_1' => $query->whereMonth('created_at', $currentMonth->copy()->subMonth()->month)
                                              ->whereYear('created_at', $currentMonth->copy()->subMonth()->year),
                            'month_2' => $query->whereMonth('created_at', $currentMonth->copy()->subMonths(2)->month)
                                              ->whereYear('created_at', $currentMonth->copy()->subMonths(2)->year),
                            'month_3' => $query->whereMonth('created_at', $currentMonth->copy()->subMonths(3)->month)
                                              ->whereYear('created_at', $currentMonth->copy()->subMonths(3)->year),
                            'month_4' => $query->whereMonth('created_at', $currentMonth->copy()->subMonths(4)->month)
                                              ->whereYear('created_at', $currentMonth->copy()->subMonths(4)->year),
                            'month_5' => $query->whereMonth('created_at', $currentMonth->copy()->subMonths(5)->month)
                                              ->whereYear('created_at', $currentMonth->copy()->subMonths(5)->year),
                            'month_6' => $query->whereMonth('created_at', $currentMonth->copy()->subMonths(6)->month)
                                              ->whereYear('created_at', $currentMonth->copy()->subMonths(6)->year),
                            default => $query,
                        };
                    }),
               
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Tables\Actions\ViewAction::make()
                    //     ->url(fn (Quote $record): ?string => route('filament.admin.resources.quotes.view', ['record' => $record->id])),
//                    Tables\Actions\Action::make('print')
//                        ->url(fn (Quote $record): string => route('filament.app.resources.health-sherpas.print', $record))
//                        ->label('Imprimir')
//                        ->icon('iconoir-printing-page'),
                    Tables\Actions\EditAction::make(),
                    ConvertToPolicy::make('convert_to_policy')
                        ->label('Crear Poliza')
                        ->icon('iconoir-privacy-policy'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-m-ellipsis-vertical'),
                Tables\Actions\Action::make('print')
                    ->url(fn (Quote $record): string => QuoteResource::getUrl('print', ['record' => $record]))
                    ->label('Imprimir')
                    ->icon('iconoir-printing-page'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    // public static function getRelations(): array
    // {
    //     return [
    //         RelationManagers\QuoteDocumentsRelationManager::class,
    //     ];
    // }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
//            'view' => Pages\ViewQuote::route('/{record}'),
            'print' => Pages\PrintQuote::route('/{record}/print'),
        ];
    }
}
