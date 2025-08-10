<?php

namespace App\Filament\Resources\CommissionStatementResource\Pages;

use App\Filament\Resources\CommissionStatementResource;
use App\Models\CommissionStatement;
use App\Models\Policy;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CommissionRun extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cash';

    protected static ?string $navigationLabel = 'Comisiones';

    protected static ?string $navigationGroup = 'Finance';

    protected static string $view = 'filament.resources.commission-statement-resource.pages.commission-run';

    public $user_id;

    public $until_date;

    public $selectedPolicies = [];

    public $totalCommission = 0;

    // Store custom commission rates temporarily
    public array $customRates = [];

    // Bonus amount and notes
    public $bonus_amount = 0;

    public $bonus_notes = '';

    // Policy type amounts for summary display
    public $healthAmount = 0;

    public $accidentAmount = 0;

    public $visionAmount = 0;

    public $dentalAmount = 0;

    public $lifeAmount = 0;

    public $totalPoliciesAmount = 0;

    protected static string $resource = CommissionStatementResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Seleccionar Asistente')
                            ->options(User::all()->pluck('name', 'id'))
                            ->required(),
                        Forms\Components\DatePicker::make('until_date')
                            ->label('Pay Commissions Until')
                            ->default(now())
                            ->required(),
                        Forms\Components\Placeholder::make('instructions')
                            ->label('')
                            ->content('Select an agent and date, then click "Find Policies" to see commissionable policies.'),
                    ])
                    ->columns(2),
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('bonus_amount')
                            ->label('Bonus Amount')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\Textarea::make('bonus_notes')
                            ->label('Bonus Notes')
                            ->placeholder('Enter reason for bonus or any additional notes')
                            ->rows(2),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Commission Summary')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Placeholder::make('health_amount_summary')
                                    ->label('Health Policies')
                                    ->content(fn () => '$'.number_format($this->healthAmount, 2)),
                                Forms\Components\Placeholder::make('accident_amount_summary')
                                    ->label('Accident Policies')
                                    ->content(fn () => '$'.number_format($this->accidentAmount, 2)),
                                Forms\Components\Placeholder::make('vision_amount_summary')
                                    ->label('Vision Policies')
                                    ->content(fn () => '$'.number_format($this->visionAmount, 2)),
                                Forms\Components\Placeholder::make('dental_amount_summary')
                                    ->label('Dental Policies')
                                    ->content(fn () => '$'.number_format($this->dentalAmount, 2)),
                                Forms\Components\Placeholder::make('life_amount_summary')
                                    ->label('Life Policies')
                                    ->content(fn () => '$'.number_format($this->lifeAmount, 2)),
                            ])
                            ->columns(3),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Placeholder::make('total_policies_amount')
                                    ->label('Total Policies Commission')
                                    ->content(fn () => '$'.number_format($this->totalPoliciesAmount, 2))
                                    ->extraAttributes(['class' => 'text-lg font-bold']),
                                Forms\Components\Placeholder::make('bonus_amount_summary')
                                    ->label('Bonus Amount')
                                    ->content(fn () => '$'.number_format($this->bonus_amount, 2)),
                                Forms\Components\Placeholder::make('total_with_bonus')
                                    ->label('Total Commission')
                                    ->content(fn () => '$'.number_format($this->totalPoliciesAmount + (float) $this->bonus_amount, 2))
                                    ->extraAttributes(['class' => 'text-xl font-bold text-primary-600']),
                            ])
                            ->columns(3),
                    ])
                    ->hidden(fn () => $this->totalPoliciesAmount <= 0)
                    ->collapsible(),
            ]);

    }

    protected function getTableQuery()
    {
        if (! $this->user_id || ! $this->until_date) {
            return Policy::query()->where('id', 0); // Empty query if no agent/date selected
        }

        return Policy::query()
            ->where('user_id', $this->user_id)
            ->where('status', 'active')
            ->whereNotNull('activation_date')
            ->where('activation_date', '<=', $this->until_date)
            ->whereNull('commission_statement_id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->recordClasses(fn (Policy $record) => 'cursor-pointer')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Policy #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Client')
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
                                $medicaidBadge = '<span class="px-2 py-0.5 bg-indigo-900/10 text-indigo-900 rounded-md text-xs font-medium">Medicaid</span>';
                            }

                            $customers .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1px;">
                                <span style="color: #6b7280; font-size: 0.75rem; max-width: 70%;">'.$applicant->full_name.'</span>
                                '.$medicaidBadge.'
                            </div>';
                        }

                        // Add horizontal line
                        $customers .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 8px; margin-bottom: 6px;"></div>';

                        $enrollmentType = $record->policy_inscription_type?->getLabel() ?? 'N/A';
                        $customers .= '<div style="display: flex; align-items: center;">
                            <span style="font-size: 0.75rem; color: #374151; font-weight: 500;">Tipo de Inscripción:</span>
                            <span style="font-size: 0.75rem; color: #6b7280; margin-left: 4px;">'.$enrollmentType.'</span>
                        </div>';

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
                Tables\Columns\TextColumn::make('total_applicants')
                    ->label('Applicantes Adicionales')
                    ->formatStateUsing(fn ($state) => $state - 1),
                Tables\Columns\TextColumn::make('activation_date')
                    ->label('Fecha Activación')
                    ->date('m-d-Y'),
                Tables\Columns\TextColumn::make('commission_rate_per_policy')
                    ->label('Comisión Poliza')
                    ->getStateUsing(function (Policy $record) {
                        return $this->customRates[$record->id]['base_rate'] ?? $record->commission_rate_per_policy ?? 10;
                    })
                    ->numeric(),
                Tables\Columns\TextColumn::make('commission_rate_per_additional_applicant')
                    ->label('Applicante Adicional')
                    ->getStateUsing(function (Policy $record) {
                        return $this->customRates[$record->id]['additional_rate'] ?? $record->commission_rate_per_additional_applicant ?? 5;
                    })
                    ->numeric(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Total')
                    ->getStateUsing(function (Policy $record) {
                        $baseCommission = $this->customRates[$record->id]['base_rate'] ?? $record->commission_rate_per_policy ?? 10;
                        $additionalApplicantsCommission = (($record->total_applicants ?? 1) - 1) *
                            ($this->customRates[$record->id]['additional_rate'] ?? $record->commission_rate_per_additional_applicant ?? 5);

                        return $baseCommission + $additionalApplicantsCommission;
                    })
                    ->money('USD')
                    ->badge()
                    ->color(fn (Policy $record) => isset($this->customRates[$record->id]) ? 'warning' : null),
            ])
            ->actions([
                Tables\Actions\Action::make('customizeRates')
                    ->label('Custom Rates')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->modalHeading('Customize Commission Rates')
                    ->modalDescription(fn (Policy $record) => "Setting custom rates for policy #{$record->code}")
                    ->modalSubmitActionLabel('Save Custom Rates')
                    ->modalWidth('md')
                    ->form([
                        Forms\Components\TextInput::make('base_rate')
                            ->label('Base Commission Rate')
                            ->required()
                            ->numeric()
                            ->default(function (Policy $record) {
                                return $this->customRates[$record->id]['base_rate'] ?? $record->commission_rate_per_policy ?? 10;
                            }),
                        Forms\Components\TextInput::make('additional_rate')
                            ->label('Additional Applicant Rate')
                            ->required()
                            ->numeric()
                            ->default(function (Policy $record) {
                                return $this->customRates[$record->id]['additional_rate'] ?? $record->commission_rate_per_additional_applicant ?? 5;
                            }),
                        Forms\Components\Placeholder::make('note')
                            ->content('These rates will only be applied when the final statement is generated.')
                            ->extraAttributes(['class' => 'text-sm text-gray-500']),
                    ])
                    ->action(function (Policy $record, array $data): void {
                        // Store in component state, not in database
                        $this->customRates[$record->id] = [
                            'base_rate' => $data['base_rate'],
                            'additional_rate' => $data['additional_rate'],
                        ];

                        Notification::make()
                            ->title('Custom rates saved temporarily')
                            ->body('The rates will be applied when the final statement is generated.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('generate_statement')
                    ->label('Generate Commission Statement')
                    ->icon('heroicon-o-document-text')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        if ($records->isEmpty()) {
                            Notification::make()->danger()->title('No policies selected.')->send();

                            return;
                        }

                        DB::transaction(function () use ($records) {
                            // Calculate commission amounts by policy type
                            $this->calculatePolicyTypeAmounts($records);

                            // Add bonus amount to total
                            $bonusAmount = (float) $this->bonus_amount;
                            $totalWithBonus = $this->totalPoliciesAmount + $bonusAmount;

                            // Create the commission statement
                            $statement = CommissionStatement::create([
                                'user_id' => $this->user_id,
                                'statement_date' => now(),
                                'end_date' => $this->until_date,
                                'total_amount' => $totalWithBonus,
                                'status' => 'Generated',
                                'created_by' => auth()->id(),
                                'health_policy_amount' => $this->healthAmount,
                                'accident_policy_amount' => $this->accidentAmount,
                                'vision_policy_amount' => $this->visionAmount,
                                'dental_policy_amount' => $this->dentalAmount,
                                'life_policy_amount' => $this->lifeAmount,
                                'bonus_amount' => $bonusAmount,
                                'bonus_notes' => $this->bonus_notes,
                            ]);

                            // Link the policies to this statement and update rates if customized
                            foreach ($records as $policy) {
                                $updateData = ['commission_statement_id' => $statement->id];

                                // Always save the rates and commission amount, whether they were customized or not
                                $baseRate = isset($this->customRates[$policy->id])
                                    ? $this->customRates[$policy->id]['base_rate']
                                    : ($policy->commission_rate_per_policy ?? 10);

                                $additionalRate = isset($this->customRates[$policy->id])
                                    ? $this->customRates[$policy->id]['additional_rate']
                                    : ($policy->commission_rate_per_additional_applicant ?? 5);

                                $additionalApplicants = ($policy->total_applicants ?? 1) - 1;
                                $commissionAmount = $baseRate + ($additionalApplicants * $additionalRate);

                                // Update all fields
                                $updateData['commission_rate_per_policy'] = $baseRate;
                                $updateData['commission_rate_per_additional_applicant'] = $additionalRate;
                                $updateData['commission_amount'] = $commissionAmount;

                                $policy->update($updateData);
                            }

                            // Clear the custom rates after generating the statement
                            $this->customRates = [];
                        });

                        Notification::make()
                            ->title('Commission statement generated successfully.')
                            ->success()
                            ->send();
                        $this->resetTableFiltersForm();

                        // Redirect to the index page to see the newly created statement
                        $this->redirect(CommissionStatementResource::getUrl('index'));
                    }),
            ]);
    }

    // Calculation is now handled in the bulk action

    public function findPolicies()
    {
        $this->validate([
            'user_id' => 'required',
            'until_date' => 'required|date',
        ]);

        // Reset selected policies when changing agent or date
        $this->selectedPolicies = [];
        $this->totalCommission = 0;
        $this->resetPolicyTypeAmounts();

        // The table will automatically refresh with the new query parameters
    }

    public function updatedTableSelectedRecords($selectedRecords)
    {
        if (empty($selectedRecords)) {
            $this->resetPolicyTypeAmounts();

            return;
        }

        // Get the selected policies
        $policies = Policy::whereIn('id', $selectedRecords)->get();
        $this->calculatePolicyTypeAmounts($policies);
    }

    protected function resetPolicyTypeAmounts()
    {
        $this->healthAmount = 0;
        $this->accidentAmount = 0;
        $this->visionAmount = 0;
        $this->dentalAmount = 0;
        $this->lifeAmount = 0;
        $this->totalPoliciesAmount = 0;
    }

    protected function calculatePolicyTypeAmounts($policies)
    {
        $this->resetPolicyTypeAmounts();

        foreach ($policies as $policy) {
            $baseCommission = $this->customRates[$policy->id]['base_rate'] ?? $policy->commission_rate_per_policy ?? 10;
            $additionalApplicantsCommission = (($policy->total_applicants ?? 1) - 1) *
                ($this->customRates[$policy->id]['additional_rate'] ?? $policy->commission_rate_per_additional_applicant ?? 5);
            $policyCommission = $baseCommission + $additionalApplicantsCommission;

            // Add to the appropriate policy type amount
            $policyType = $policy->policy_type?->value ?? 'health';
            switch ($policyType) {
                case 'health':
                    $this->healthAmount += $policyCommission;
                    break;
                case 'accident':
                    $this->accidentAmount += $policyCommission;
                    break;
                case 'vision':
                    $this->visionAmount += $policyCommission;
                    break;
                case 'dental':
                    $this->dentalAmount += $policyCommission;
                    break;
                case 'life':
                    $this->lifeAmount += $policyCommission;
                    break;
                default:
                    $this->healthAmount += $policyCommission;
            }

            $this->totalPoliciesAmount += $policyCommission;
        }
    }

    // Statement generation is now handled in the bulk action
}
