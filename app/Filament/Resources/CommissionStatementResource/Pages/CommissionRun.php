<?php

namespace App\Filament\Resources\CommissionStatementResource\Pages;

use App\Filament\Resources\CommissionStatementResource;
use App\Models\CommissionStatement;
use App\Models\Policy;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

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
                    ->formatStateUsing(fn ($state) => $state -  1),
                Tables\Columns\TextColumn::make('activation_date')
                    ->label('Fecha Activación')
                    ->date('m-d-Y'),
                Tables\Columns\TextInputColumn::make('commission_rate_per_policy')
                    ->label('Comisión Poliza')
                    ->type('number')
                    ->default(10),
                Tables\Columns\TextInputColumn::make('commission_rate_per_additional_applicant')
                    ->label('Applicante Adicional')
                    ->type('number')
                    ->default(5),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Total')
                    ->getStateUsing(function ($record) {
                        $baseCommission = $record->commission_rate_per_policy ?? 10;
                        $additionalApplicantsCommission = (($record->total_applicants ?? 1) - 1) * ($record->commission_rate_per_additional_applicant ?? 5);
                        return $baseCommission + $additionalApplicantsCommission;
                    })
                    ->money('USD'),
            ])
            ->actions([
                // Add any row actions if needed
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('generate_statement')
                    ->label('Generate Commission Statement')
                    ->icon('heroicon-o-document-text')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) {
                        if ($records->isEmpty()) {
                            $this->notify('error', 'No policies selected.');
                            return;
                        }

                        DB::transaction(function () use ($records) {
                            // Calculate total commission using the same logic as calculateTotal
                            $totalAmount = 0;
                            foreach ($records as $policy) {
                                $baseCommission = $policy->commission_rate_per_policy ?? 10;
                                $additionalApplicantsCommission = (($policy->total_applicants ?? 1) - 1) * ($policy->commission_rate_per_additional_applicant ?? 5);
                                $totalAmount += $baseCommission + $additionalApplicantsCommission;
                            }

                            // Create the commission statement
                            $statement = CommissionStatement::create([
                                'user_id' => $this->user_id,
                                'statement_date' => now(),
                                'end_date' => $this->until_date,
                                'total_commission' => $totalAmount,
                                'status' => 'Generated',
                                'created_by' => auth()->id(),
                            ]);

                            // Link the policies to this statement
                            foreach ($records as $policy) {
                                $policy->update(['commission_statement_id' => $statement->id]);
                            }
                        });

                        Notification::make()
                            ->title('Commission statement generated successfully.')
                            ->success()
                            ->send();
                        $this->resetTableFiltersForm();
                        
                        // Redirect to the index page to see the newly created statement
                        $this->redirect(CommissionStatementResource::getUrl('index'));
                    })
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
        
        // Set default commission values for policies
        // $policies = $this->getTableQuery()->get();
        // foreach ($policies as $policy) {
        //     $policy->commission_rate_per_policy = 10;
        // }
        
        // The table will automatically refresh with the new query parameters
    }

    // Statement generation is now handled in the bulk action
}
