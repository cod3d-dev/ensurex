<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Filament\Resources\PolicyResource;
use App\Models\Contact;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditPolicyIncome extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Ingresos';

    protected static ?string $navigationIcon = 'iconoir-money-square';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('total_family_members')
                    ->numeric()
                    ->label('Total Miembros Familiares')
                    ->required()
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->default(1)
                    ->live()
                    ->afterStateHydrated(function (string $state, Forms\Set $set) {
                        $kinectKPL = \App\Models\KynectFPL::getCurrentThreshold((int) $state);
                        $set('kynect_fpl_threshold', number_format($kinectKPL * 12, 2, '.', ','));
                    })
                    ->afterStateUpdated(function (string $state, Forms\Set $set) {
                        $kinectKPL = \App\Models\KynectFPL::getCurrentThreshold((int) $state);
                        $set('kynect_fpl_threshold', number_format($kinectKPL * 12, 2, '.', ','));
                    }),
                Forms\Components\TextInput::make('total_applicants')
                    ->label('Total Aplicantes')
                    ->readOnly()
                    ->extraInputAttributes(['class' => 'text-center'])
                    ->formatStateUsing(function ($state, $record) {
                        return $record->policyApplicants()->where('is_covered_by_policy', true)->count();
                    }),
                Forms\Components\TextInput::make('estimated_household_income')
                    ->label('Ingreso Familiar Estimado')
                    ->prefix('$')
                    ->readOnly()
                    ->extraInputAttributes(function (Forms\Get $get, $state) {
                        $income = floatval(str_replace(',', '', $state ?? 0));
                        $threshold = floatval(str_replace(',', '', $get('kynect_fpl_threshold') ?? 0));

                        $classes = 'text-end';

                        if ($income < $threshold) {
                            $classes .= ' custom-input-color-red';
                        }

                        return ['class' => $classes];
                    })
                    ->numeric(),
                Forms\Components\TextInput::make('kynect_fpl_threshold')
                    ->label('Ingresos Requeridos Kynect')
                    ->disabled()
                    ->extraInputAttributes(['class' => 'text-end'])
                    ->prefix('$')
                    ->live()
                    ->formatStateUsing(function ($state, $get) {
                        $memberCount = $get('total_family_members') ?? 1;
                        $kinectKPL = floatval(\App\Models\KynectFPL::getCurrentThreshold($memberCount));

                        return number_format($kinectKPL * 12, 2, '.', ',');
                    })
                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                        $memberCount = $get('total_family_members') ?? 1;
                        $kinectKPL = floatval(\App\Models\KynectFPL::getCurrentThreshold($memberCount));
                        $set('kynect_fpl_threshold', $kinectKPL * 12);
                    }),

                Forms\Components\Repeater::make('policyApplicants')
                    ->relationship()
                    ->label('Aplicantes Adicionales')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->collapsible(true)
                    ->hiddenLabel()
                    ->itemLabel(function (array $state): ?string {
                        if (isset($state['contact_id'])) {
                            $contact = Contact::find($state['contact_id']);
                            $relationship = isset($state['relationship_with_policy_owner']) ? ' - '.FamilyRelationship::tryFrom($state['relationship_with_policy_owner'])->getLabel() : '';
                            $covered = isset($state['is_covered_by_policy']) && $state['is_covered_by_policy'] ? ' - Cubierto' : ' - No Cubierto';
                            $medicaid = isset($state['medicaid_client']) && $state['medicaid_client'] ? ' - Cliente Medicaid' : '';

                            return $contact->full_name.$relationship.$covered.$medicaid;
                        }

                        return null;
                    })
                    ->schema([
                        Forms\Components\TextInput::make('employer_1_name')
                            ->label('Empleador'),
                        Forms\Components\TextInput::make('employer_1_role')
                            ->label('Cargo'),
                        Forms\Components\TextInput::make('employer_1_phone')
                            ->label('Teléfono'),
                        Forms\Components\TextInput::make('employer_1_address')
                            ->label('Dirección')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('income_per_hour')
                            ->numeric()
                            ->label('Hora $')
                            ->live(onBlur: true)
                            ->formatStateUsing(fn ($state) => number_format($state, 2, '.', ','))
                            ->disabled(fn (Forms\Get $get
                            ): bool => $get('is_self_employed') ?? false)
                            ->afterStateUpdated(fn (
                                $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ) => static::calculateYearlyIncome('applicant', $state, $set,
                                $get)),
                        Forms\Components\TextInput::make('hours_per_week')
                            ->numeric()
                            ->label('Horas/Semana')
                            ->live(onBlur: true)
                            ->disabled(fn (Forms\Get $get
                            ): bool => $get('is_self_employed') ?? false)
                            ->afterStateUpdated(fn (
                                $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ) => static::calculateYearlyIncome('applicant', $state, $set,
                                $get)),
                        Forms\Components\TextInput::make('income_per_extra_hour')
                            ->numeric()
                            ->label('Hora Extra $')
                            ->live(onBlur: true)
                            ->disabled(fn (Forms\Get $get
                            ): bool => $get('is_self_employed') ?? false)
                            ->afterStateUpdated(fn (
                                $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ) => static::calculateYearlyIncome('applicant', $state, $set,
                                $get)),
                        Forms\Components\TextInput::make('extra_hours_per_week')
                            ->numeric()
                            ->label('Extra/Semana')
                            ->live(onBlur: true)
                            ->disabled(fn (Forms\Get $get
                            ): bool => $get('is_self_employed') ?? false)
                            ->afterStateUpdated(fn (
                                $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ) => static::calculateYearlyIncome('applicant', $state, $set,
                                $get)),
                        Forms\Components\TextInput::make('weeks_per_year')
                            ->numeric()
                            ->label('Semanas por Año')
                            ->live(onBlur: true)
                            ->disabled(fn (Forms\Get $get
                            ): bool => $get('is_self_employed') ?? false)
                            ->afterStateUpdated(fn (
                                $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ) => static::calculateYearlyIncome('applicant', $state, $set,
                                $get)),
                        Forms\Components\TextInput::make('yearly_income')
                            ->numeric()
                            ->label('Ingreso Anual')
                            ->readOnly(),
                        Forms\Components\Toggle::make('is_self_employed')
                            ->label('¿Self Employeed?')
                            ->inline(false)
                            ->live()
                            ->columnStart(4)
                            // ->afterStateHydrated(fn(
                            //     $state,
                            //     Forms\Set $set,
                            //     Forms\Get $get
                            // ) => static::calculateYearlyIncome('applicant', $state, $set,
                            //     $get))
                            ->afterStateUpdated(function (
                                $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ) {
                                static::lockHourlyIncome('applicant', $state, $set, $get);
                                static::calculateYearlyIncome('applicant', $state, $set,
                                    $get);
                            }),
                        Forms\Components\TextInput::make('self_employed_profession')
                            ->label('Profesión')
                            ->disabled(fn (Forms\Get $get
                            ): bool => ! $get('is_self_employed')),
                        Forms\Components\TextInput::make('self_employed_yearly_income')
                            ->numeric()
                            ->label('Ingreso Anual')
                            ->live(onBlur: true)
                            ->columnStart(6)
                            ->disabled(fn (Forms\Get $get
                            ): bool => ! $get('is_self_employed'))
                            ->afterStateUpdated(fn (
                                $state,
                                Forms\Set $set,
                                Forms\Get $get
                            ) => static::calculateYearlyIncome('applicant', $state, $set,
                                $get)),
                    ])->columns(6)->columnSpanFull()
                    ->collapseAllAction(fn (\Filament\Forms\Components\Actions\Action $action) => $action->hidden())
                    ->expandAllAction(fn (\Filament\Forms\Components\Actions\Action $action) => $action->hidden()),
            ])->columns(4);

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

        $policyApplicants = $get('../../policyApplicants') ?? [];
        if (empty($policyApplicants)) {
            $policyApplicants = $get('policyApplicants') ?? [];
        }

        $AllApplicantsYearlyIncome = 0;

        foreach ($policyApplicants as $index => $policyApplicant) {

            $applicantYearlyIncome = 0;
            if ($policyApplicant['is_self_employed']) {
                $applicantYearlyIncome = floatval($policyApplicant['self_employed_yearly_income'] ?? 0);
            } else {
                $incomePerHour = floatval($policyApplicant['income_per_hour'] ?? 0);
                $hoursPerWeek = floatval($policyApplicant['hours_per_week'] ?? 0);
                $incomePerExtraHour = floatval($policyApplicant['income_per_extra_hour'] ?? 0);
                $extraHoursPerWeek = floatval($policyApplicant['extra_hours_per_week'] ?? 0);
                $weeksPerYear = floatval($policyApplicant['weeks_per_year'] ?? 0);

                $applicantYearlyIncome = ($incomePerHour * $hoursPerWeek + $incomePerExtraHour * $extraHoursPerWeek) * $weeksPerYear;
            }

            $AllApplicantsYearlyIncome += $applicantYearlyIncome;

        }

        $totalYearlyIncome = $AllApplicantsYearlyIncome;

        $set('../../estimated_household_income', number_format($totalYearlyIncome, 2, '.', ''));
        $set('estimated_household_income', $totalYearlyIncome);
    }
}
