<?php

namespace App\Filament\Resources\CommissionStatementResource\RelationManagers;

use App\Models\Policy;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'policies';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('policy')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('policy')
            ->defaultGroup('policy_type')
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Poliza #')
                    ->summarize(Count::make()->label('Total Policies')),
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
                Tables\Columns\TextColumn::make('activation_date')
                    ->label('Fecha Activación')
                    ->date('m-d-Y'),
                Tables\Columns\TextColumn::make('commission_rate_per_policy')
                    ->label('Comisión')
                    ->numeric()
                    ->summarize(Sum::make()->label('Total Comisión')),
                Tables\Columns\TextColumn::make('total_applicants')
                    ->label('Adicionales')
                    ->formatStateUsing(fn ($state) => $state - 1)
                    ->summarize(Count::make()->label('Total Adicionales')),
                Tables\Columns\TextColumn::make('commission_rate_per_additional_applicant')
                    ->label('Comisión')
                    ->numeric()
                    ->summarize(Sum::make()->label('Total Comisión')),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($state) => '$'.$state)
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
