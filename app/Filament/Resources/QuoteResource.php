<?php

namespace App\Filament\Resources;

use App\Enums\FamilyRelationship;
use App\Enums\Gender;
use App\Enums\PolicyType;
use App\Enums\QuoteStatus;
use App\Enums\UsState;
use App\Filament\Resources\QuoteResource\Actions\ConvertToPolicy;
use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Models\Contact;
use App\Models\Quote;
use App\Services\GoogleMapsService;
use App\Tables\Columns\PoliciesColumn;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions;
// use Filament\Forms\FormEvents;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
// use Awcodes\TableRepeater\Components\TableRepeater;
// use Awcodes\TableRepeater\Header;
// use App\Actions\ResetStars;
use Filament\Forms\Form;
// use App\Actions\Star;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
                Section::make('Cotización')
                    ->schema([
                        Forms\Components\Grid::make()  // Quote details
                            ->schema([
                                Forms\Components\Select::make('contact_id')
                                    ->label('Nombre')
                                    ->relationship('contact', 'full_name')
                                    ->searchable()
                                    ->live()
                                    ->options(function () {
                                        return Contact::all()
                                            ->mapWithKeys(function ($contact) {
                                                $details = [];

                                                // Calculate age from date_of_birth if available
                                                if ($contact->date_of_birth) {
                                                    $age = \Carbon\Carbon::parse($contact->date_of_birth)->age;
                                                    $details[] = "{$age} años";
                                                }

                                                if ($contact->state_province) {
                                                    $state = $contact->state_province instanceof \App\Enums\UsState
                                                        ? $contact->state_province->value
                                                        : $contact->state_province;
                                                    $details[] = $state;
                                                }

                                                $detailsText = $details ? ' ('.implode(', ', $details).')' : '';

                                                return [
                                                    $contact->id => $contact->full_name.$detailsText,
                                                ];
                                            })
                                            ->toArray();
                                    })
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('full_name')
                                                    ->label('Nombre')
                                                    ->columnSpan(2)
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\DatePicker::make('date_of_birth')
                                                    ->label('Fecha de Nacimiento'),
                                                Forms\Components\Select::make('gender')
                                                    ->label('Género')
                                                    ->options(Gender::class),
                                            ])
                                            ->columns(['md' => 4, 'lg' => 4, 'xl' => 4]),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $contact = Contact::create($data);

                                        return $contact->id;
                                    })
                                    ->columnSpan(4)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $contact = Contact::find($state);
                                        if (! $contact) {
                                            return;
                                        }
                                        $set('contact.full_name', $contact->full_name);
                                        $set('contact.date_of_birth', $contact->date_of_birth);
                                        $set('contact.gender', $contact->gender);
                                        $set('contact.age', $contact->age);
                                        $set('contact.phone', $contact->phone);
                                        $set('contact.phone2', $contact->phone2);
                                        $set('contact.kommo_id', $contact->kommo_id);
                                        $set('contact.email_address', $contact->email_address);
                                        $set('contact.city', $contact->city);
                                        $set('contact.county', $contact->county);
                                        $set('contact.state_province', $contact->state_province);
                                        $set('contact.zip_code', $contact->zip_code);

                                        $applicants = $get('applicants') ?: [];

                                        // Create a new applicant entry for the selected contact
                                        $newApplicant = [
                                            'relationship' => FamilyRelationship::Self->value,
                                            'full_name' => $contact->full_name,
                                            'date_of_birth' => $contact->date_of_birth,
                                            'age' => $contact->age,
                                            'is_covered' => true,
                                            'gender' => $contact->gender,
                                            'is_pregnant' => false,
                                            'is_tobacco_user' => false,
                                            'is_self_employed' => false,
                                            'is_eligible_for_coverage' => false,
                                            'employeer_name' => '',
                                            'employement_role' => '',
                                            'employeer_phone' => '',
                                            'income_per_hour' => '',
                                            'hours_per_week' => '',
                                            'income_per_extra_hour' => '',
                                            'extra_hours_per_week' => '',
                                            'weeks_per_year' => '',
                                            'yearly_income' => '',
                                            'self_employed_yearly_income' => '',
                                        ];

                                        // If applicants array is empty, add the new applicant
                                        // Otherwise, update the first applicant with the new data
                                        if (empty($applicants)) {
                                            $applicants[] = $newApplicant;
                                        } else {
                                            // Get the first key in the array (could be any string/number)
                                            $firstKey = array_key_first($applicants);
                                            $applicants[$firstKey] = array_merge($applicants[$firstKey] ?? [], $newApplicant);
                                        }

                                        $set('applicants', $applicants);
                                    })
                                    ->afterStateHydrated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $contact = Contact::find($state);
                                        if (! $contact) {
                                            return;
                                        }
                                        $set('contact.full_name', $contact->full_name);
                                        $set('contact.date_of_birth', $contact->date_of_birth);
                                        $set('contact.gender', $contact->gender);
                                        $set('contact.age', $contact->age);
                                        $set('contact.phone', $contact->phone);
                                        $set('contact.phone2', $contact->phone2);
                                        $set('contact.kommo_id', $contact->kommo_id);
                                        $set('contact.email_address', $contact->email_address);
                                        $set('contact.city', $contact->city);
                                        $set('contact.county', $contact->county);
                                        $set('contact.state_province', $contact->state_province);
                                        $set('contact.zip_code', $contact->zip_code);

                                        $applicants = $get('applicants') ?: [];

                                        // Create a new applicant entry for the selected contact
                                        $newApplicant = [
                                            'relationship' => FamilyRelationship::Self->value,
                                            'full_name' => $contact->full_name,
                                            'date_of_birth' => $contact->date_of_birth,
                                            'age' => $contact->age,
                                            'is_covered' => true,
                                            'gender' => $contact->gender,
                                            'is_pregnant' => false,
                                            'is_tobacco_user' => false,
                                            'is_self_employed' => false,
                                            'is_eligible_for_coverage' => false,
                                            'employeer_name' => '',
                                            'employement_role' => '',
                                            'employeer_phone' => '',
                                            'income_per_hour' => '',
                                            'hours_per_week' => '',
                                            'income_per_extra_hour' => '',
                                            'extra_hours_per_week' => '',
                                            'weeks_per_year' => '',
                                            'yearly_income' => '',
                                            'self_employed_yearly_income' => '',
                                        ];

                                        // If applicants array is empty, add the new applicant
                                        // Otherwise, update the first applicant with the new data
                                        if (empty($applicants)) {
                                            $applicants[] = $newApplicant;
                                        } else {
                                            // Get the first key in the array (could be any string/number)
                                            $firstKey = array_key_first($applicants);
                                            $applicants[$firstKey] = array_merge($applicants[$firstKey] ?? [], $newApplicant);
                                        }

                                        $set('applicants', $applicants);
                                    }),
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
                                Forms\Components\Select::make('year')
                                    ->required()
                                    ->columnSpan(2)
                                    ->label('Año')
                                    ->options(function () {
                                        $startYear = 2018;
                                        $endYear = Carbon::now()->addYears(2)->year;
                                        $years = [];

                                        for ($year = $startYear; $year <= $endYear; $year++) {
                                            $years[$year] = $year;
                                        }

                                        return $years;
                                    })
                                    ->default(Carbon::now()->year),
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
                                            ->visible(fn (Forms\Get $get): bool => $get('policy_id') !== null)
                                            ->url(function (Forms\Get $get) {
                                                $policyId = $get('policy_id');

                                                if ($policyId) {
                                                    return PolicyResource::getUrl('view', ['record' => $policyId]);
                                                }

                                                return '';
                                            })
                                            ->openUrlInNewTab()
                                    ),
                                Forms\Components\CheckboxList::make('policy_types')
                                    ->options(PolicyType::class)
                                    ->extraInputAttributes(['class' => 'text-left'])
                                    ->columnStart(5)
                                    ->inlineLabel()
                                    ->required()
                                    ->columnStart(1)
                                    ->label('Tipo de Póliza')
                                    ->columns(['md' => 6])
                                    ->columnSpanFull(),
                            ])
                            ->columns(['md' => 12, 'lg' => 12, 'xl' => 12]),
                        Section::make('Datos del Titular')
                            ->schema([
                                Forms\Components\TextInput::make('contact.full_name')
                                    ->label('Nombre')
                                    ->columnSpan(4),
                                Forms\Components\DatePicker::make('contact.date_of_birth')
                                    ->label('Fecha Nacimiento')
                                    ->live(onBlur: true)
                                    ->afterStateHydrated(function ($state, Forms\Set $set, $get) {
                                        if ($state) {
                                            $birthDate = \Carbon\Carbon::parse($state);
                                            $age = $birthDate->age;
                                            $set('contact.age', $age);

                                            // Update the first applicant in the repeater
                                            $applicants = $get('applicants') ?? [];
                                            if (count($applicants) > 0) {
                                                $firstKey = array_key_first($applicants);
                                                $applicants[$firstKey]['date_of_birth'] = $birthDate->format('Y-m-d');
                                                $firstKey = array_key_first($applicants);
                                                $applicants[$firstKey]['age'] = $age;
                                                $set('applicants', $applicants);
                                            }
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        if ($state) {
                                            $birthDate = \Carbon\Carbon::parse($state);
                                            $age = $birthDate->age;
                                            $set('contact.age', $age);

                                            // Update the first applicant in the repeater
                                            $applicants = $get('applicants') ?? [];
                                            if (count($applicants) > 0) {
                                                $firstKey = array_key_first($applicants);
                                                $applicants[$firstKey]['date_of_birth'] = $birthDate->format('Y-m-d');
                                                $firstKey = array_key_first($applicants);
                                                $applicants[$firstKey]['age'] = $age;
                                                $set('applicants', $applicants);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('contact.age')
                                    ->dehydrated(false)
                                    ->label('Edad'),
                                Forms\Components\Select::make('contact.gender')
                                    ->label('Género')
                                    ->placeholder('Genero')
                                    ->options(Gender::class)
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('contact.phone')
                                    ->label('Teléfono')
                                    ->columnStart(1)
                                    ->tel()
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('contact.phone2')
                                    ->label('Teléfono 2')
                                    ->tel()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('contact.kommo_id')
                                    ->label('Kommo ID')
                                    ->columnSpan(2)
                                    ->suffixAction(
                                        Action::make('copyCostToPrice')
                                            ->icon('gmdi-get-app')
                                            ->form([
                                                Forms\Components\TextInput::make('url')
                                                    ->label('URL del Chat')
                                                    ->required(),
                                            ])
                                            ->action(function (Forms\Set $set, $data) {
                                                $url = $data['url'] ?? '';

                                                // Extract the ID using regex
                                                if (preg_match('/\/detail\/([0-9]+)/', $url, $matches)) {
                                                    $kommoId = $matches[1];
                                                    $set('contact.kommo_id', $kommoId);

                                                    // Show success notification
                                                    Notification::make()
                                                        ->title('Kommo ID actualizado')
                                                        ->body('Se extrajo el ID '.$kommoId.' de la URL proporcionada.')
                                                        ->success()
                                                        ->send();
                                                } else {
                                                    // Show error notification
                                                    Notification::make()
                                                        ->title('Error en el formato de URL')
                                                        ->body('La URL proporcionada no tiene el formato esperado. El ID de Kommo no ha sido actualizado.')
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ),
                                Forms\Components\TextInput::make('contact.email_address')
                                    ->label('Correo Electrónico')
                                    ->email()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('contact.zip_code')
                                    ->columnStart(1)
                                    ->label('Código Postal')
                                    ->required()
                                    ->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                        if ($state !== null && strlen($state) === 5 && is_numeric($state)) {
                                            $googleMapsService = app(GoogleMapsService::class);
                                            $locationData = $googleMapsService->getLocationFromZipCode($state);

                                            if ($locationData) {
                                                $set('contact.city', $locationData['city']);
                                                $set('contact.state_province', $locationData['state']);
                                                $set('contact.county', $locationData['county']);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('contact.county')
                                    ->label('Condado')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('contact.city')
                                    ->label('Ciudad')
                                    ->columnSpan(3)
                                    ->required(),
                                Forms\Components\Select::make('contact.state_province')
                                    ->label('Estado')
                                    ->columnSpan(3)
                                    ->placeholder('Estado')
                                    ->options(UsState::class)
                                    ->searchable()
                                    ->required(),
                            ])
                            ->columns(['md' => 10]),
                        Section::make('Aplicantes')
                            ->schema([
                                Forms\Components\TextInput::make('total_family_members')
                                    ->numeric()
                                    ->label('Total Familiares')
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (string $state, Forms\Set $set) {
                                        $kinectKPL = \App\Models\KynectFPL::threshold(2024, (int) $state);
                                        $set('kynect_fpl_threshold', $kinectKPL * 12);
                                    }),
                                Forms\Components\TextInput::make('total_applicants')
                                    ->numeric()
                                    ->label('Total Solicitantes')
                                    ->required()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $applicants = $get('applicants') ?? [];
                                        $applicants_count = count($applicants);

                                        // If we need more rows than we currently have
                                        if ($state > $applicants_count) {
                                            // Keep existing members
                                            //                                                    $newFamilyMembers = $familyMembers;

                                            // Add new empty rows
                                            for ($i = $applicants_count; $i < $state; $i++) {
                                                $applicants[] = [
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
                                            $applicants = array_slice($applicants, 0,
                                                $state);
                                        }

                                        $set('applicants', $applicants);
                                    }),
                                Forms\Components\TextInput::make('estimated_household_income')
                                    ->numeric()
                                    ->label('Ingresos Estimados')
                                    ->prefix('$')
                                    ->extraInputAttributes(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $kynectFplThreshold = $get('kynect_fpl_threshold');
                                        $estimatedHouseholdIncome = $state;
                                        if ($estimatedHouseholdIncome < $kynectFplThreshold) {
                                            return ['style' => 'background-color: #FEE2E2;'];
                                        }

                                        return [];
                                    }),
                                Forms\Components\TextInput::make('kynect_fpl_threshold')
                                    ->label('Requerido Kynect')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->prefix('$')
                                    ->live()
                                    ->formatStateUsing(function ($state, $get) {
                                        $memberCount = $get('total_family_members') ?? 1;
                                        $kinectKPL = floatval(\App\Models\KynectFPL::threshold(2024,
                                            $memberCount));

                                        return $kinectKPL * 12;
                                    }),
                            ])
                            ->columns(4),
                        Forms\Components\Repeater::make('applicants')
                            ->label('Aplicantes')
                            ->addable(false)
                            ->deletable(false)
                            ->defaultItems(0)
                            // ->hiddenLabel(true)
                            ->schema([
                                Forms\Components\Select::make('relationship')
                                    ->label('Relación con el Titular')
                                    ->options(FamilyRelationship::class)
                                    ->disableOptionWhen(fn ($state, $value): bool => ($state === null && $value === 'self') ||
                                        ($state !== null && $value === 'self') ||
                                        $state === 'self')
                                    ->columnSpan(2)
                                    ->required(),
                                Forms\Components\TextInput::make('full_name')
                                    ->label('Nombre')
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('date_of_birth')
                                    ->label('Fecha Nac.')
                                    ->columnSpan(2)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                                        if ($state) {
                                            $birthDate = \Carbon\Carbon::parse($state);
                                            $age = $birthDate->age;
                                            $set('age', $age);

                                            // Only update contact if this is the 'self' relationship
                                            if (($get('relationship') ?? '') === 'self') {
                                                $set('../../contact.date_of_birth', $birthDate->format('Y-m-d'));
                                                $set('../../contact.age', $age);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('age')
                                    ->label('Edad'),
                                Forms\Components\Toggle::make('is_covered')
                                    ->label('Aplicante')
                                    ->inline(false)
                                    ->default(true),
                                Forms\Components\Select::make('gender')
                                    ->label('Género')
                                    ->options(Gender::class)
                                    ->columnSpan(2)
                                    ->columnStart(4)
                                    ->required(),
                                Forms\Components\Toggle::make('is_pregnant')
                                    ->label('Embarazada')
                                    ->columnStart(7)
                                    ->inline(false)
                                    ->default(false),
                                Forms\Components\Toggle::make('is_tobacco_user')
                                    ->label('Fuma')
                                    ->inline(false)
                                    ->default(false),
                                Forms\Components\Toggle::make('is_eligible_for_coverage')
                                    ->label('Medicaid')
                                    ->inline(false)
                                    ->default(false),
                                Forms\Components\Fieldset::make('Ingresos')
                                    ->schema([
                                        Forms\Components\TextInput::make('employeer_name')
                                            ->label('Empresa')
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('employement_role')
                                            ->label('Cargo')
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('employeer_phone')
                                            ->label('Teléfono')
                                            ->columnSpan(3),
                                        Forms\Components\TextInput::make('income_per_hour')
                                            ->numeric()
                                            ->label('Hora $')
                                            ->live(onBlur: true)
                                            ->columnSpan(2)
                                            ->afterStateUpdated(fn (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) => static::calculateYearlyIncome(
                                                'additional',
                                                $state,
                                                $set,
                                                $get
                                            )),
                                        Forms\Components\TextInput::make('hours_per_week')
                                            ->numeric()
                                            ->label('H/S')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) => static::calculateYearlyIncome(
                                                'additional',
                                                $state,
                                                $set,
                                                $get
                                            )),
                                        Forms\Components\TextInput::make('income_per_extra_hour')
                                            ->numeric()
                                            ->label('H/Extra $')
                                            ->columnSpan(2)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) => static::calculateYearlyIncome(
                                                'additional',
                                                $state,
                                                $set,
                                                $get
                                            )),
                                        Forms\Components\TextInput::make('extra_hours_per_week')
                                            ->numeric()
                                            ->label('H/E S')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) => static::calculateYearlyIncome(
                                                'additional',
                                                $state,
                                                $set,
                                                $get
                                            )),
                                        Forms\Components\TextInput::make('weeks_per_year')
                                            ->numeric()
                                            ->label('S/Año')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) => static::calculateYearlyIncome(
                                                'additional',
                                                $state,
                                                $set,
                                                $get
                                            )),
                                        Forms\Components\TextInput::make('yearly_income')
                                            ->numeric()
                                            ->label('Ingreso Anual')
                                            ->disabled()
                                            ->columnSpan(2),
                                        Forms\Components\Toggle::make('is_self_employed')
                                            ->label('¿Self Employeed?')
                                            ->inline(false)
                                            ->live(onBlur: true)
                                            ->columnSpan(2)
                                            ->columnStart(6)
                                            ->afterStateHydrated(fn (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) => static::calculateYearlyIncome(
                                                'applicant',
                                                $state,
                                                $set,
                                                $get
                                            ))
                                            ->afterStateUpdated(function (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) {
                                                static::lockHourlyIncome(
                                                    'additional',
                                                    $state,
                                                    $set,
                                                    $get
                                                );
                                                static::calculateYearlyIncome(
                                                    'additional',
                                                    $state,
                                                    $set,
                                                    $get
                                                );
                                            }),
                                        Forms\Components\TextInput::make('self_employed_yearly_income')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->label('Ingreso Anual')
                                            ->columnSpan(2)
                                            ->afterStateUpdated(fn (
                                                $state,
                                                Forms\Set $set,
                                                Forms\Get $get
                                            ) => static::calculateYearlyIncome(
                                                'additional',
                                                $state,
                                                $set,
                                                $get
                                            )),
                                    ])
                                    ->columns(9),
                            ])
                            ->columns(9)
                            ->itemLabel(function (array $state): ?string {
                                $relationshipValue = $state['relationship'] ?? null;
                                $fullName = $state['full_name'] ?? null;
                                $age = $state['age'] ?? null;

                                if ($relationshipValue) {
                                    // Get the label from the enum instead of using the raw value
                                    $relationshipLabel = $relationshipValue;

                                    // Convert the value to enum label if it's a valid enum value
                                    if ($relationshipValue) {
                                        try {
                                            $enum = \App\Enums\FamilyRelationship::from($relationshipValue);
                                            $relationshipLabel = $enum->getLabel();
                                        } catch (\ValueError $e) {
                                            // If not a valid enum value, keep using the raw value
                                        }
                                    }

                                    $label = $relationshipLabel;

                                    if ($fullName) {
                                        $label .= ' - '.$fullName;
                                    }

                                    if ($age) {
                                        $label .= ' ('.$age.' años)';
                                    }

                                    return $label;
                                }

                                return null;
                            })
                            ->reorderable(false)
                            ->collapsible()
                            ->columnSpanFull(),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('total_family_members')
                                    ->numeric()
                                    ->label('Total Familiares')
                                    ->required()
                                    ->columnStart(2)
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
                                        $applicants = $get('applicants') ?? [];
                                        $applicants_count = count($applicants);

                                        // If we need more rows than we currently have
                                        if ($state > $applicants_count) {
                                            // Keep existing members
                                            //                                                    $newFamilyMembers = $familyMembers;

                                            // Add new empty rows
                                            for ($i = $applicants_count; $i < $state; $i++) {
                                                $applicants[] = [
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
                                            $applicants = array_slice($applicants, 0,
                                                $state);
                                        }

                                        $set('applicants', $applicants);
                                    }),
                                Forms\Components\TextInput::make('estimated_household_income')
                                    ->numeric()
                                    ->label('Ingresos Estimados')
                                    ->prefix('$')
                                    ->extraInputAttributes(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $kynectFplThreshold = $get('kynect_fpl_threshold');
                                        $estimatedHouseholdIncome = $state;
                                        if ($estimatedHouseholdIncome < $kynectFplThreshold) {
                                            return ['style' => 'background-color: #FEE2E2;'];
                                        }

                                        return [];
                                    }),
                                Forms\Components\TextInput::make('kynect_fpl_threshold')
                                    ->label('Requerido Kynect')
                                    ->readOnly()
                                    ->dehydrated(false)
                                    ->prefix('$')
                                    ->live()
                                    ->formatStateUsing(function ($state, $get) {
                                        $memberCount = $get('total_family_members') ?? 1;
                                        $kinectKPL = floatval(\App\Models\KynectFPL::threshold(2024,
                                            $memberCount));

                                        return $kinectKPL * 12;
                                    }),
                            ])
                            ->columns(6),
                        Section::make('Otros')
                            ->schema([

                                Forms\Components\Textarea::make('notes')
                                    ->label('Notas')
                                    ->readOnly()
                                    ->rows(5),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('add_note')
                                        ->label('Agregar Nota')
                                        ->color('info')
                                        ->form([
                                            Forms\Components\Textarea::make('note')
                                                ->label('Nota')
                                                ->required(),
                                        ])
                                        ->action(function (array $data, Forms\Set $set, array $state) {
                                            $user = auth()->user();
                                            $userName = $user ? $user->name : 'Unknown User';
                                            $dateTime = Carbon::now()->format('Y-m-d H:i:s');
                                            $formattedNote = "[{$dateTime}] {$userName}:\n{$data['note']}";
                                            $set('notes', $state['notes']."\n\n".$formattedNote);
                                        }),
                                ])->alignment(Alignment::Right),

                                Section::make([
                                    // Add a section to view existing documents and add new ones
                                    Actions::make([
                                        Action::make('Health Sherpa')
                                            ->icon('heroicon-m-star')
                                            ->url('https://www.healthsherpa.com/shopping?_agent_id=nil&carrier_id=nil&source=agent-home'),
                                        Action::make('Kommo')
                                            ->icon('heroicon-m-x-mark')
                                            ->color('success')
                                            ->url(fn (
                                                Forms\Get $get
                                            ) => 'https://ghercys.kommo.com/leads/detail/'.$get('contact_information.kommo_id')),
                                    ])->alignment(Alignment::Right),
                                ]),
                            ]),
                    ])
                    ->columnSpanFull(),
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

        $applicants = $get('../../applicants') ?? [];

        $AllApplicantsYearlyIncome = 0;

        foreach ($applicants as $index => $applicant) {
            $applicantYearlyIncome = 0;
            if ($applicant['is_self_employed']) {
                $applicantYearlyIncome = floatval($applicant['self_employed_yearly_income'] ?? 0);
            } else {
                $incomePerHour = floatval($applicant['income_per_hour'] ?? 0);
                $hoursPerWeek = floatval($applicant['hours_per_week'] ?? 0);
                $incomePerExtraHour = floatval($applicant['income_per_extra_hour'] ?? 0);
                $extraHoursPerWeek = floatval($applicant['extra_hours_per_week'] ?? 0);
                $weeksPerYear = floatval($applicant['weeks_per_year'] ?? 0);

                $applicantYearlyIncome = ($incomePerHour * $hoursPerWeek + $incomePerExtraHour * $extraHoursPerWeek) * $weeksPerYear;
            }

            $AllApplicantsYearlyIncome += $applicantYearlyIncome;
        }

        $totalYearlyIncome = $AllApplicantsYearlyIncome;

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
                    ->formatStateUsing(fn (string $state): string => Str::acronym($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.code')
                    ->label('Asistente')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Cliente')
                    ->sortable(['first_name', 'last_name'])
                    ->searchable(['first_name', 'last_name', 'middle_name', 'second_last_name'])
                    ->description(fn ($record) => 'Applicantes: '.$record->total_applicants),
                PoliciesColumn::make('policy_types')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('m/d/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Asistente')
                    ->relationship('user', 'name')
                    ->default(function () {
                        $user = auth()->user();
                        // Only set default filter for non-admin users
                        if ($user->role !== \App\Enums\UserRoles::Admin) {
                            return $user->id;
                        }

                        return null;
                    }),
                Tables\Filters\SelectFilter::make('agent.name')
                    ->label('Agente')
                    ->relationship('agent', 'name'),
                Tables\Filters\SelectFilter::make('year')
                    ->label('Año Efectivo')
                    ->options(function () {
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
                    ->multiple()
                    ->label('Estatus')
                    ->columnSpan(2)
                    ->options(QuoteStatus::class)
                    ->default([QuoteStatus::Pending->value, QuoteStatus::Accepted->value, QuoteStatus::Sent->value]),
                Tables\Filters\SelectFilter::make('created_week')
                    ->label('Semana de Creación')
                    ->options(function () {
                        $options = [];
                        $currentWeek = Carbon::now()->startOfWeek();

                        // Current week
                        $weekStart = $currentWeek->copy()->format('Y-m-d');
                        $weekEnd = $currentWeek->copy()->endOfWeek()->format('Y-m-d');
                        $options['current'] = "Semana Actual ({$weekStart} - {$weekEnd})";

                        // Past 3 weeks
                        for ($i = 1; $i <= 3; $i++) {
                            $weekStart = $currentWeek->copy()->subWeeks($i)->format('Y-m-d');
                            $weekEnd = $currentWeek->copy()->subWeeks($i)->endOfWeek()->format('Y-m-d');
                            $options["week_{$i}"] = "Hace {$i} semana".($i > 1 ? 's' : '')." ({$weekStart} - {$weekEnd})";
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
                    ->options(function () {
                        $options = [];
                        $currentMonth = Carbon::now();

                        // Current month
                        $options['current'] = 'Mes Actual ('.$currentMonth->format('F Y').')';

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
                            'current' => $query
                                ->whereMonth('created_at', $currentMonth->month)
                                ->whereYear('created_at', $currentMonth->year),
                            'month_1' => $query
                                ->whereMonth('created_at', $currentMonth->copy()->subMonth()->month)
                                ->whereYear('created_at', $currentMonth->copy()->subMonth()->year),
                            'month_2' => $query
                                ->whereMonth('created_at', $currentMonth->copy()->subMonths(2)->month)
                                ->whereYear('created_at', $currentMonth->copy()->subMonths(2)->year),
                            'month_3' => $query
                                ->whereMonth('created_at', $currentMonth->copy()->subMonths(3)->month)
                                ->whereYear('created_at', $currentMonth->copy()->subMonths(3)->year),
                            'month_4' => $query
                                ->whereMonth('created_at', $currentMonth->copy()->subMonths(4)->month)
                                ->whereYear('created_at', $currentMonth->copy()->subMonths(4)->year),
                            'month_5' => $query
                                ->whereMonth('created_at', $currentMonth->copy()->subMonths(5)->month)
                                ->whereYear('created_at', $currentMonth->copy()->subMonths(5)->year),
                            'month_6' => $query
                                ->whereMonth('created_at', $currentMonth->copy()->subMonths(6)->month)
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
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar')
                        ->hidden(fn () => auth()->user()->role !== \App\Enums\UserRoles::Admin),

                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->defaultSort('created_at', 'asc');
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
            'view' => Pages\ViewQuote::route('/{record}'),
            'print' => Pages\PrintQuote::route('/{record}/print'),
        ];
    }
}
