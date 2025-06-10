<?php

namespace App\Filament\Resources;

use App\Enums\DocumentStatus;
use App\Enums\PolicyInscriptionType;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\RenewalStatus;
use App\Enums\UserRoles;
use App\Enums\UsState;
use App\Filament\Resources\PolicyResource\Pages;
use App\Filament\Resources\PolicyResource\RelationManagers;
use App\Filament\Resources\PolicyResource\Widgets\PolicyStats;
use App\Models\Contact;
use App\Models\Policy;
// use App\Filament\Resources\PolicyResource\RelationManagers\IssuesRelationManager;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PolicyResource extends Resource
{
    protected static ?string $model = Policy::class;

    protected static ?string $navigationIcon = 'iconoir-privacy-policy';

    protected static ?string $navigationGroup = 'Polizas';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Polizas';

    protected static ?string $modelLabel = 'Poliza';

    protected static ?string $pluralModelLabel = 'Polizas';

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'contact.first_name';

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->contact->full_name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['contact.first_name', 'contact.middle_name', 'contact.last_name', 'contact.second_last_name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Tipo' => $record->policy_type->getLabel() ?? null,
            'Año' => $record->policy_year,
            // Return Pagado if $record->initial_paid is true
            'Estatus' => (string) (($record->initial_paid === true ? 'Pagado' : 'Sin Pagar').' / Documentos: '.($record->document_status->getLabel())),
            'Cliente Notificado' => ($record->client_notified === true ? 'Sí' : 'No').($record->contact->state_province == 'KY' ? ' / ACA: '.($record->aca === true ? 'Sí' : 'No') : ''),

        ];
    }

    public static function getWidgets(): array
    {
        return [
            PolicyStats::class,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $record = $page->getRecord();

        $pages = [
            //            Pages\ViewPolicy::class,
            Pages\EditPolicy::class,
            Pages\EditPolicyContact::class,
            Pages\EditPolicyApplicants::class,
            Pages\EditPolicyApplicantsData::class,
            Pages\EditPolicyIncome::class,
            Pages\EditPolicyPayments::class,
            Pages\ManagePolicyDocument::class,
            Pages\ManagePolicyIssues::class,
            Pages\EditCompletePolicyCreation::class,

        ];

        // if ($record->areRequiredPagesCompleted() === true) {
        //     $pages[] = Pages\EditCompletePolicyCreation::class;
        // }

        return $page->generateNavigationItems($pages);
    }

    public static function form(Form $form): Form
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
                            // Disable is not admin or supervisor the policy doesn't belong to the user
                            ->disabled(fn (Get $get): bool => ! auth()->user()->role->isAdmin() && ! auth()->user()->role->isSupervisor() && $get('user_id') != auth()->user()->id)
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
                        Forms\Components\DatePicker::make('effective_date')
                            ->label('Inicio')
                            ->columnSpan(2)
                            ->extraInputAttributes([
                                'class' => 'text-center',
                            ]),
                        Forms\Components\Select::make('policy_inscription_type')
                            ->options(PolicyInscriptionType::class)
                            ->label('Tipo de Inscripción')
                            ->columnSpan(2),

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
                                    ->columnSpan(1)
                                    ->label('Pedir Caso Kynect'),
                                Forms\Components\TextInput::make('total_family_members')
                                    ->label('Familiares')
                                    ->extraInputAttributes([
                                        'class' => 'text-center',
                                    ])
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('total_applicants')
                                    ->label('Aplicantes')
                                    ->extraInputAttributes([
                                        'class' => 'text-center',
                                    ])
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('total_applicants_with_medicaid')
                                    ->label('Medicaid')
                                    ->extraInputAttributes([
                                        'class' => 'text-center',
                                    ])
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('estimated_household_income')
                                    ->label('Ingresos Estimados')
                                    ->extraInputAttributes([
                                        'class' => 'text-center',
                                    ])
                                    ->numeric()
                                    ->columnSpan(2),
                            ])->columns(['sm' => 4, 'md' => 8, 'lg' => 8, 'xl' => 8])->columnSpanFull(),

                        Forms\Components\Fieldset::make()
                            ->schema([

                                Forms\Components\Select::make('status')
                                    ->label('Estatus')
                                    ->columnSpan(2)
                                    ->disabled()
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
                                    ->disabled(fn (Forms\Get $get): bool => $get('policy_us_state') != UsState::KENTUCKY->value),
                                Forms\Components\Toggle::make('is_initial_verification_complete')
                                    ->inline(false)
                                    ->disabled()
                                    ->live()
                                    ->columnSpan(2)
                                    ->label('Verificacion Inicial'),
                                Forms\Components\Select::make('initial_verification_performed_by')
                                    ->relationship('initialVerificationPerformedBy', 'name')
                                    ->disabled()
                                    ->label('Verificado Por')
                                    ->disabled(fn (Get $get) => $get('is_initial_verification_complete') != true)
                                    ->columnSpan(3),
                                Forms\Components\DatePicker::make('initial_verification_date')
                                    ->label('Fecha Verificacion')
                                    ->disabled(fn (Get $get) => $get('is_initial_verification_complete') != true)
                                    ->disabled()
                                    ->columnSpan(3),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Observaciones')
                                    ->rows(6)
                                    ->readOnly()
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
                                            $note = Carbon::now()->toDateTimeString().' - '.auth()->user()->name.":\n".$data['note']."\n\n";
                                            $record->notes = ! empty($record->notes) ? $record->notes."\n\n".$note : $note;
                                            $record->save();

                                            // Refresh the notes field with the updated value
                                            $set('notes', $record->notes);
                                        }),
                                    Forms\Components\Actions\Action::make('verification')
                                        ->label('Marcar como Verificada')
                                        ->color('success')
                                        ->modalHeading('Verificación')
                                        ->visible(fn (?Policy $record) => $record && $record->exists && $record->status === PolicyStatus::ToVerify)
                                        ->form([
                                            Forms\Components\Select::make('status')
                                                ->options(PolicyStatus::class)
                                                ->disableOptionWhen(fn (string $value): bool => ($value === PolicyStatus::ToVerify->value)
                                                )
                                                ->required(),
                                            Forms\Components\Textarea::make('note')
                                                ->required()
                                                ->rows(3)
                                                ->label('Nota'),
                                        ])
                                        ->modalSubmitActionLabel('Verificación')
                                        ->action(function (Policy $record, array $data, Set $set): void {
                                            $note = 'Verificada el '.Carbon::now()->toDateTimeString().' por '.auth()->user()->name;
                                            $note = $note.":\n".$data['note']."\n\n";
                                            $record->notes = ! empty($record->notes) ? $record->notes."\n\n".$note : $note;
                                            $record->status = $data['status'];
                                            $record->is_initial_verification_complete = true;
                                            $record->initial_verification_performed_by = auth()->user()->id;
                                            $record->initial_verification_date = Carbon::now();
                                            $record->save();

                                            // Refresh the notes field with the updated value
                                            $set('notes', $record->notes);
                                            // $this->redirect(PolicyResource::getUrl('edit', ['record' => $this->record]));
                                        }),
                                ])
                                    ->alignEnd()
                                    ->columnSpanFull(),
                            ])
                            ->columns(['md' => 8, 'lg' => 8])
                            ->columnSpanFull(),
                    ])->columns(['md' => 8, 'lg' => 8]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Insurance Account name
                Tables\Columns\TextColumn::make('contact.id')
                    // format getting the first letter of each word in the name in uppercase
                    ->label('#')
                    ->badge()
                    // ->tooltip(fn(string $state): string => $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent.name')
                    // format getting the first letter of each word in the name in uppercase
                    ->formatStateUsing(fn (string $state): string => Str::acronym($state))
                    ->label('Cuenta')
                    ->badge()
                    // ->tooltip(fn(string $state): string => $state)
                    ->color(fn (string $state): string => match ($state) {
                        'Ghercy Segovia' => 'success',
                        'Maly Carvajal' => 'primary',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Cliente')
                    ->grow(false)
                    ->searchable()
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->whereHas('contact', function (Builder $query) use ($search) {
                                $query->where('full_name', 'like', "%{$search}%");
                            })
                            ->orWhereHas('applicants', function (Builder $query) use ($search) {
                                $query->where('full_name', 'like', "%{$search}%");
                            });
                    })
                    ->tooltip(function (string $state, Policy $record): string {
                        $spanishMonths = [
                            'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
                            'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
                            'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre',
                        ];
                        $month = $record->contact->created_at->format('F');
                        $year = $record->contact->created_at->format('Y');
                        $spanishDate = $spanishMonths[$month].' de '.$year;
                        $customers = 'Cliente desde '.$spanishDate;

                        return $customers;
                    })
                    ->formatStateUsing(function (string $state, Policy $record): string {
                        $customers = $state;
                        foreach ($record->additionalApplicants() as $applicant) {
                            $medicaidBadge = '';
                            if ($applicant->pivot->medicaid_client) {
                                $medicaidBadge = '<span style="display: inline-block; background-color: #60a5fa; color: white; border-radius: 0.2rem; padding: 0rem 0.2rem; font-size: 0.75rem; font-weight: 500; margin-left: 15px;">Medicaid</span>';
                            }

                            $customers .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1px;">
                                <span style="color: #6b7280; font-size: 0.75rem; max-width: 70%;">'.$applicant->full_name.'</span>
                                '.$medicaidBadge.'
                            </div>';
                        }

                        // Add horizontal line
                        $customers .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 8px; margin-bottom: 6px;"></div>';

                        // Add enrollment type
                        $enrollmentType = $record->policy_inscription_type->getLabel() ?? 'N/A';
                        $customers .= '<div style="display: flex; align-items: center;">
                            <span style="font-size: 0.75rem; color: #374151; font-weight: 500;">Tipo de Inscripción:</span>
                            <span style="font-size: 0.75rem; color: #6b7280; margin-left: 4px;">'.$enrollmentType.'</span>
                        </div>';

                        // // Add another horizontal line
                        // $customers .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 8px; margin-bottom: 6px;"></div>';

                        // Add status indicators
                        $customers .= '<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">';

                        // Define badge styles to exactly match Filament's design from screenshot
                        $successBadgeStyle = 'display: inline-block; background-color: rgb(240, 253, 244); color: rgb(22, 163, 74); border-radius: 0.375rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; line-height: 1;';
                        $dangerBadgeStyle = 'display: inline-block; background-color: rgb(254, 242, 242); color: rgb(220, 38, 38); border-radius: 0.375rem; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; line-height: 1;';

                        // Client notified indicator
                        $badgeStyle = $record->client_notified ? $successBadgeStyle : $dangerBadgeStyle;
                        $customers .= '<span style="'.$badgeStyle.'">Informado</span>';

                        // Autopay indicator
                        $badgeStyle = $record->autopay ? $successBadgeStyle : $dangerBadgeStyle;
                        $customers .= '<span style="'.$badgeStyle.'">Autopay</span>';

                        // Initial paid indicator
                        $badgeStyle = $record->initial_paid ? $successBadgeStyle : $dangerBadgeStyle;
                        $customers .= '<span style="'.$badgeStyle.'">Inicial</span>';

                        // ACA indicator (only if requires_aca is true)
                        if ($record->requires_aca) {
                            $badgeStyle = $record->aca ? $successBadgeStyle : $dangerBadgeStyle;
                            $customers .= '<span style="'.$badgeStyle.'">ACA</span>';
                        }

                        // FPL indicator
                        $latestFPL = \App\Models\KynectFPL::latest()->first();
                        $meetsFPL = false;

                        if ($latestFPL) {
                            $householdSize = $record->total_family_members;
                            $annualIncome = (float) $record->estimated_household_income;

                            // Calculate threshold based on household size
                            $threshold = null;
                            if ($householdSize <= 8) {
                                $memberField = "members_{$householdSize}";
                                $threshold = $latestFPL->{$memberField} * 12;
                            } else {
                                $baseAmount = $latestFPL->members_8;
                                $extraMembers = $householdSize - 8;
                                $threshold = ($baseAmount + ($latestFPL->additional_member * $extraMembers)) * 12;
                            }

                            // Check if meets requirement
                            $meetsFPL = $annualIncome >= $threshold;
                        }

                        $badgeStyle = $meetsFPL ? $successBadgeStyle : $dangerBadgeStyle;
                        $customers .= '<span style="'.$badgeStyle.'">Ingresos</span>';

                        $customers .= '</div>';

                        return $customers;
                    }),
                Tables\Columns\TextColumn::make('policy_type')
                    ->badge()
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('insuranceCompany.code')
                    ->label('Empresa')
                    ->sortable()
                    ->badge()
                    ->default('NA')
                    ->color(fn (string $state): string => match ($state) {
                        'NA' => 'danger',
                        default => 'success',
                    })
                    ->tooltip(function ($record) {
                        if (! $record || ! $record->insuranceCompany) {
                            return '';
                        }

                        return $record->insuranceCompany->name;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('insuranceCompany', function (Builder $query) use ($search): Builder {
                            return $query->where('name', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->date('m-d-Y')
                    ->label('Fecha')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.code')
                    ->label('Asistente')
                    ->sortable()
                    ->badge()
                    ->tooltip(function (string $state, Policy $record): string {
                        return $record->user->name;
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'CR' => 'success',
                        'RS' => 'primary',
                        'OO' => 'warning',
                        'FS' => 'danger',
                        'GS' => 'info',
                        'CH' => 'gray',
                        'RM' => 'violet',
                        'MC' => 'purple',
                        'AG' => 'danger',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_status')
                    ->label('Documentos')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_date')
                    ->label('Fecha de inicio')
                    ->date('m-d-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Válida hasta')
                    ->date('m-d-Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_renewal')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Renovada')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Asistente')
                    ->relationship('user', 'name')
                    ->default(auth()->user()->id),
                Tables\Filters\SelectFilter::make('agent.name')
                    ->label('Agente')
                    ->relationship('agent', 'name'),
                Tables\Filters\SelectFilter::make('policy_year')
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options(PolicyStatus::class),
                Tables\Filters\SelectFilter::make('document_status')
                    ->label('Documentos')
                    ->multiple()
                    ->options(DocumentStatus::class),
                Tables\Filters\SelectFilter::make('insurance_company_id')
                    ->relationship('insuranceCompany', 'name')
                    ->label('Compañía de Seguro')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('policy_type')
                    ->options(PolicyType::class)
                    ->label('Tipo de Poliza'),
                Tables\Filters\SelectFilter::make('policy_us_state')
                    ->label('Estado')
                    ->options(UsState::class),
                Tables\Filters\SelectFilter::make('medicaid_filter')
                    ->label('Aplicantes con Medicaid')
                    ->options([
                        '0' => 'Sin Medicaid',
                        '1' => 'Con Medicaid',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        if ($data['value'] === '1') {
                            // Return policies where at least one policy applicant has medicaid_client = true
                            return $query->whereHas('policyApplicants', function ($q) {
                                $q->where('medicaid_client', true);
                            });
                        }

                        // Return policies where no policy applicants have medicaid_client = true
                        return $query->whereDoesntHave('policyApplicants', function ($q) {
                            $q->where('medicaid_client', true);
                        });
                    }),
                Tables\Filters\Filter::make('meetsKynectFPLRequirement')
                    ->label('Cumple FPL')
                    ->form([
                        Forms\Components\Select::make('meets_fpl')
                            ->label('Cumple FPL')
                            ->options([
                                'yes' => 'Sí',
                                'no' => 'No',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        if (empty($data['meets_fpl'])) {
                            return $query;
                        }

                        // Get the latest KynectFPL record
                        $latestFPL = \App\Models\KynectFPL::latest()->first();

                        if (! $latestFPL) {
                            return $query;
                        }

                        // Get all policies with their family members
                        $policies = $query->get(['id', 'total_family_members', 'estimated_household_income']);

                        // Filter the policies based on FPL requirements
                        $filteredIds = $policies->filter(function ($policy) use ($latestFPL, $data) {
                            $householdSize = $policy->total_family_members;
                            $annualIncome = (float) $policy->estimated_household_income;

                            // Calculate threshold based on household size
                            $threshold = null;
                            if ($householdSize <= 8) {
                                $memberField = "members_{$householdSize}";
                                $threshold = $latestFPL->{$memberField} * 12;
                            } else {
                                $baseAmount = $latestFPL->members_8;
                                $extraMembers = $householdSize - 8;
                                $threshold = ($baseAmount + ($latestFPL->additional_member * $extraMembers)) * 12;
                            }

                            // Check if meets requirement based on selection
                            if ($data['meets_fpl'] === 'yes') {
                                return $annualIncome >= $threshold;
                            } else {
                                return $annualIncome < $threshold;
                            }
                        })->pluck('id')->toArray();

                        // Return query with filtered IDs
                        return $query->whereIn('id', $filteredIds);
                    }),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                // Create a Action group with 3 actions
                ActionGroup::make([
                    Tables\Actions\Action::make('change_status')
                        ->label('Cambiar Estatus')
                        ->icon('mdi-tag-edit-outline')
                        ->form([
                            Forms\Components\Split::make([
                                Forms\Components\Grid::make(1)
                                    ->schema([
                                        Forms\Components\Select::make('status')
                                            ->label('Estatus')
                                            ->options(PolicyStatus::class)
                                            ->required()
                                            ->preload()
                                            ->disableOptionWhen(fn (string $value): bool => ($value === PolicyStatus::ToVerify->value)
                                            )
                                            ->searchable(),
                                        Forms\Components\TextArea::make('notas')
                                            ->label('Notas')
                                            ->required(),
                                    ]),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('client_notified')
                                            ->label('Cliente Informado')
                                            ->default(fn (Policy $record) => $record->client_notified),
                                        Forms\Components\Toggle::make('initial_paid')
                                            ->label('Inicial Pagada')
                                            ->default(fn (Policy $record) => $record->initial_paid),
                                        Forms\Components\Toggle::make('autopay')
                                            ->label('Cotizacion')
                                            ->default(fn (Policy $record) => $record->autopay),
                                        Forms\Components\Toggle::make('aca')
                                            ->label('ACA')
                                            ->visible(fn (Policy $record) => $record->requires_aca)
                                            ->default(fn (Policy $record) => $record->aca),
                                    ]),
                            ]),
                        ])
                        ->action(function (Policy $record, array $data): void {
                            $record->status = $data['status'];
                            $note = Carbon::now()->toDateTimeString().' - '.auth()->user()->name.":\nCambio de Estatus: ".PolicyStatus::from($data['status'])->getLabel()."\n".$data['notas']."\n\n";
                            $record->notes = ! empty($record->notes) ? $record->notes."\n\n".$note : $note;
                            $record->client_notified = $data['client_notified'];
                            $record->initial_paid = $data['initial_paid'];
                            $record->autopay = $data['autopay'];
                            if ($record->requires_aca) {
                                $record->aca = $data['aca'];
                            }
                            $record->save();

                            Notification::make()
                                ->title('Estatus Cambiado')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('view')
                        ->label('Ver')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Policy $record): string => PolicyResource::getUrl('view', ['record' => $record])),
                    // Tables\Actions\Action::make('view')
                    //     ->label('Ver')
                    //     ->icon('heroicon-o-eye')
                    //     ->url(fn (Policy $record): string => PolicyResource::getUrl('view-compact', ['record' => $record])),
                    Tables\Actions\EditAction::make(),
                    // Tables\Actions\Action::make('quickedit')
                    //     ->label('Edición Rápida')
                    //     ->icon('heroicon-o-pencil-square')
                    //     ->url(fn (Policy $record): string => route('filament.app.resources.policies.quickedit', $record)),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (?Policy $record) => $record &&
                            $record->exists &&
                            auth()->user()->role === UserRoles::Admin
                        ),
                    Tables\Actions\ReplicateAction::make()
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->form([
                            Forms\Components\Select::make('policy_type')
                                ->label('Tipo de Poliza')
                                ->options(PolicyType::class)
                                ->required()
                                ->preload()
                                ->searchable(),
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
                        ->modalHeading('Duplicar Póliza')
                        ->modalDescription('Se creará una nueva póliza con los datos de la póliza actual. Por favor, seleccione el tipo de póliza y especifique las nuevas fechas.')
                        ->modalSubmitActionLabel('Duplicar')
                        ->mutateRecordDataUsing(function (array $data): array {
                            // Remove specific fields that should not be duplicated
                            unset($data['policy_id']);
                            unset($data['number']);
                            unset($data['renewed_from_policy_id']);
                            unset($data['renewed_to_policy_id']);
                            unset($data['renewed_by']);
                            unset($data['renewed_at']);

                            return $data;
                        })
                        ->beforeReplicaSaved(function (Policy $replica, array $data): void {
                            // Update with form data
                            $replica->policy_type = $data['policy_type'];
                            $replica->start_date = $data['start_date'];
                            $replica->end_date = $data['end_date'];
                            $replica->notes = ($replica->notes ? $replica->notes."\n\n" : '').
                                "=== Notas de Duplicación ===\n".($data['notes'] ?? 'Póliza duplicada el '.now()->format('Y-m-d H:i:s'));
                        })
                        ->afterReplicaSaved(function (Policy $replica): void {
                            Notification::make()
                                ->title('Póliza duplicada exitosamente')
                                ->success()
                                ->send();
                        }),
                    // Will implement the renew action later
                    // Tables\Actions\Action::make('renew')
                    //     ->label('Renovar')
                    //     ->icon('heroicon-o-arrow-path')
                    //     ->form([
                    //         Forms\Components\DatePicker::make('start_date')
                    //             ->label('Fecha de inicio')
                    //             ->required()
                    //             ->default(fn (Policy $record) => $record->getRenewalPeriod()['start_date']),
                    //         Forms\Components\DatePicker::make('end_date')
                    //             ->label('Fecha de fin')
                    //             ->required()
                    //             ->default(fn (Policy $record) => $record->getRenewalPeriod()['end_date']),
                    //         Forms\Components\TextInput::make('premium_amount')
                    //             ->label('Prima')
                    //             ->numeric()
                    //             ->default(fn (Policy $record) => $record->premium_amount)
                    //             ->required(),
                    //         Forms\Components\Textarea::make('renewal_notes')
                    //             ->label('Notas de renovación')
                    //             ->rows(3),
                    //     ])
                    //     ->action(function (Policy $record, array $data): void {
                    //         // Create new policy as renewal
                    //         $newPolicy = $record->replicate([
                    //             'number',
                    //             'status',
                    //             'renewed_from_policy_id',
                    //             'renewed_to_policy_id',
                    //             'renewed_by',
                    //             'renewed_at',
                    //             'renewal_status',
                    //             'renewal_notes',
                    //         ]);

                    //         $newPolicy->fill([
                    //             'is_renewal' => true,
                    //             'renewed_from_policy_id' => $record->id,
                    //             'renewed_by' => auth()->id(),
                    //             'renewed_at' => now(),
                    //             'renewal_status' => RenewalStatus::COMPLETED,
                    //             'renewal_notes' => $data['renewal_notes'],
                    //             'start_date' => $data['start_date'],
                    //             'end_date' => $data['end_date'],
                    //             'premium_amount' => $data['premium_amount'],
                    //             'status' => PolicyStatus::PENDING,
                    //         ]);

                    //         $newPolicy->save();

                    //         // Update original policy
                    //         $record->update([
                    //             'renewed_to_policy_id' => $newPolicy->id,
                    //         ]);

                    //         Notification::make()
                    //             ->title('Póliza renovada exitosamente')
                    //             ->success()
                    //             ->send();
                    //     })
                    //     ->requiresConfirmation()
                ])
                    // ->label('Acciones')
                    ->hiddenLabel()
                    ->icon('heroicon-m-ellipsis-horizontal')
                    ->size(ActionSize::Small)
                    ->color('primary')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('change_assistant')
                    ->label('Cambiar Asistente')
                    // Visible only if the user is admin
                    ->visible(fn (Get $get): bool => auth()->user()->role->isAdmin())
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Asistente')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        foreach ($records as $record) {
                            $record->update([
                                'user_id' => $data['user_id'],
                                'notes' => ($record->notes ? $record->notes."\n\n" : '').
                                    "=== Cambio de Asistente ===\n".
                                    'Asistente cambiado por '.auth()->user()->name.' el '.now()->format('Y-m-d H:i:s').
                                    ($data['notes'] ? "\n\n".$data['notes'] : ''),
                            ]);
                        }
                        Notification::make()
                            ->title('Asistente cambiado exitosamente')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //            RelationManagers\IssuesRelationManager::class,
            //            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getActions(): array
    {
        return [
            Actions\Action::make('renew')
                ->label('Renovar Póliza')
                ->icon('heroicon-o-arrow-path')
                ->form([
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Fecha de inicio')
                        ->required(),
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Fecha de fin')
                        ->required(),
                    Forms\Components\Textarea::make('renewal_notes')
                        ->label('Notas de renovación')
                        ->required(),
                ])
                ->action(function (Policy $record, array $data): void {
                    $newPolicy = $record->replicate([
                        'start_date',
                        'end_date',
                        'renewal_status',
                        'renewed_at',
                        'is_renewal',
                    ]);

                    $newPolicy->start_date = $data['start_date'];
                    $newPolicy->end_date = $data['end_date'];
                    $newPolicy->is_renewal = true;
                    $newPolicy->renewal_status = RenewalStatus::COMPLETED;
                    $newPolicy->renewed_at = now();
                    $newPolicy->observations = ($record->observations ? $record->observations."\n\n" : '')
                        ."=== Notas de Renovación ===\n".$data['renewal_notes'];

                    $newPolicy->save();

                    // Update the original policy's renewal status
                    $record->renewal_status = RenewalStatus::COMPLETED;
                    $record->save();

                    Notification::make()
                        ->title('Póliza renovada exitosamente')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Renovar Póliza')
                ->modalDescription('Se creará una nueva póliza con los datos de la póliza actual. Por favor, especifique las nuevas fechas y notas de renovación.')
                ->modalSubmitActionLabel('Renovar')
                ->color('success'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicies::route('/'),
            'create' => Pages\CreatePolicy::route('/create'),
            'view' => Pages\ViewPolicy::route('/{record}'),
            'view-compact' => Pages\ViewPolicyCompact::route('/{record}/compact'),
            //            'view-contact' => Pages\ViewPolicyContact::route('/{record}/contact'),
            'edit' => Pages\EditPolicy::route('/{record}/edit'),
            'edit-contact' => Pages\EditPolicyContact::route('/{record}/edit/contact'),
            'edit-applicants' => Pages\EditPolicyApplicants::route('/{record}/edit/applicants'),
            'edit-applicants-data' => Pages\EditPolicyApplicantsData::route('/{record}/edit/applicants/data'),
            'edit-income' => Pages\EditPolicyIncome::route('/{record}/edit/income'),
            'edit-others' => Pages\EditOtherPolicies::route('/{record}/edit/others'),
            'edit-complete' => Pages\EditCompletePolicyCreation::route('/{record}/edit/complete'),
            'documents' => Pages\ManagePolicyDocument::route('/{record}/documents'),
            'issues' => Pages\ManagePolicyIssues::route('/{record}/issues'),
            'payments' => Pages\EditPolicyPayments::route('/{record}/payments'),
            //            'issues' => Pages\ManagePolicyDocument::route('/{record}/documents'),
        ];
    }
}
