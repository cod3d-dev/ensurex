<?php

namespace App\Filament\Resources\CommissionStatementResource\Pages;

use App\Filament\Resources\CommissionStatementResource;
use Filament\Infolists;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Livewire\Attributes\On;

class ViewCommissionStatement extends ViewRecord
{
    protected static string $resource = CommissionStatementResource::class;

    #[On('refreshCommissionStatement')]
    public function refresh(): void
    {
        $this->refreshFormData(['total_commission', 'bonus_amount', 'total_amount']);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información')
                    ->columns(['sm' => 3, 'md' => 8, 'xl' => 8])
                    ->icon('heroicon-o-document-text')
                    ->collapsible(false)
                    ->schema([
                        Infolists\Components\TextEntry::make('statement_date')
                            ->label('Fecha')
                            ->formatStateUsing(fn ($state) => $state ? date('d/m/Y', strtotime($state)) : '-')
                            ->extraAttributes(['class' => 'font-medium']),
                        Infolists\Components\TextEntry::make('creator.name')
                            ->label('Creado por')
                            ->columnSpan(['md' => 2])
                            ->formatStateUsing(fn ($state, $record) => $record && $record->creator ? $record->creator->name : '-')
                            ->extraAttributes(['class' => 'font-medium']),
                        Infolists\Components\TextEntry::make('asistant.name')
                            ->label('Asistente')
                            ->columnSpan(['md' => 2])
                            ->formatStateUsing(fn ($state, $record) => $record && $record->asistant ? $record->asistant->name : '-')
                            ->extraAttributes(['class' => 'font-medium']),
                        Infolists\Components\TextEntry::make('month')
                            ->label('Mes')
                            ->formatStateUsing(function ($state, $record) {
                                if ($record && $record->month) {
                                    // Using Carbon to get localized month name in Spanish
                                    return \Carbon\Carbon::create(null, $record->month)->locale('es')->monthName ?? '-';
                                }

                                return '-';
                            })
                            ->extraAttributes(['class' => 'font-medium capitalize']),

                        Infolists\Components\TextEntry::make('year')
                            ->label('Año')
                            ->formatStateUsing(fn ($state) => $state ?: '-')
                            ->extraAttributes(['class' => 'font-medium']),

                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->label('Estatus')
                            ->formatStateUsing(fn ($state, $record) => $record && $record->status ? $record->status->getLabel() : '-'),

                        Infolists\Components\Section::make()
                            ->columns(['md' => 3])
                            ->schema([
                                Infolists\Components\TextEntry::make('total_commission')
                                    ->label('Comisiones')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00'),
                                Infolists\Components\TextEntry::make('bonus_amount')
                                    ->label('Bonos')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00'),
                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Total')
                                    ->formatStateUsing(fn ($record) => $record ? '$'.number_format((float) $record->total_commission + $record->bonus_amount, 2) : '$0.00'),
                            ]),
                        Actions::make([
                            Action::make('cancel')
                                ->label('Cancelar')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Cancelar declaración de comisión')
                                ->modalDescription('Esta acción liberará todas las pólizas asociadas para que puedan ser incluidas en futuras declaraciones de comisión.')
                                ->action(function () {
                                    $record = $this->getRecord();
                                    
                                    // Get all associated policies before changing status
                                    $policies = $record->policies;
                                    
                                    // Update each policy to remove the commission_statement_id
                                    foreach ($policies as $policy) {
                                        $policy->commission_statement_id = null;
                                        $policy->save();
                                    }
                                    
                                    // Update the commission statement status
                                    $record->status = \App\Enums\CommissionStatementStatus::Cancelled;
                                    $record->save();
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->title('Declaración cancelada')
                                        ->body('La declaración de comisión ha sido cancelada y ' . $policies->count() . ' pólizas han sido liberadas.')
                                        ->success()
                                        ->send();
                                    
                                    // Refresh the page to show updated data
                                    $this->redirect(\App\Filament\Resources\CommissionStatementResource::getUrl('view', ['record' => $record->id]));
                                }),
                            Action::make('paid')
                                ->label('Marcar Pagada')
                                ->color('success')
                                ->requiresConfirmation()
                                ->action(function () {
                                    $record = $this->getRecord();
                                    $record->status = \App\Enums\CommissionStatementStatus::Paid;
                                    $record->save();

                                    \Filament\Notifications\Notification::make()
                                        ->title('Estado actualizado')
                                        ->body('La declaración de comisión ha sido marcada como pagada.')
                                        ->success()
                                        ->send();
                                }),
                        ])
                            ->alignment('right')
                            ->columnSpanFull(),
                    ]),

                // Infolists\Components\Section::make('Desglose de Comisiones por Tipo de Póliza')
                //     ->description('Comisiones agrupadas por categoría de póliza')
                //     ->icon('heroicon-o-chart-pie')
                //     ->collapsible()
                //     ->schema([
                //         Infolists\Components\Grid::make(3)
                //             ->schema([
                //                 Infolists\Components\TextEntry::make('health_policy_amount')
                //                     ->label('Pólizas de Salud')
                //                     ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                //                     ->extraAttributes(['class' => 'font-medium text-primary-600']),

                //                 Infolists\Components\TextEntry::make('accident_policy_amount')
                //                     ->label('Pólizas de Accidentes')
                //                     ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                //                     ->extraAttributes(['class' => 'font-medium text-primary-600']),

                //                 Infolists\Components\TextEntry::make('vision_policy_amount')
                //                     ->label('Pólizas de Visión')
                //                     ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                //                     ->extraAttributes(['class' => 'font-medium text-primary-600']),

                //                 Infolists\Components\TextEntry::make('dental_policy_amount')
                //                     ->label('Pólizas Dentales')
                //                     ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                //                     ->extraAttributes(['class' => 'font-medium text-primary-600']),

                //                 Infolists\Components\TextEntry::make('life_policy_amount')
                //                     ->label('Pólizas de Vida')
                //                     ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                //                     ->extraAttributes(['class' => 'font-medium text-primary-600']),
                //             ])
                //             ->columns(3),
                //         // Replace the card with individual placeholders for each policy type
                //         Infolists\Components\Section::make('Commission Distribution')
                //             ->schema([
                //                 Infolists\Components\Grid::make()
                //                     ->schema(function ($get) {
                //                         $components = [];

                //                         $types = ['Health', 'Accident', 'Vision', 'Dental', 'Life'];
                //                         $fields = ['health_policy_amount', 'accident_policy_amount', 'vision_policy_amount', 'dental_policy_amount', 'life_policy_amount'];

                //                         foreach ($types as $index => $type) {
                //                             $field = $fields[$index];

                //                             $components[] = Infolists\Components\TextEntry::make($field.'_percent')
                //                                 ->label($type)
                //                                 ->content(function ($record) use ($field) {
                //                                     if (! $record || ! $record->exists) {
                //                                         return 'N/A';
                //                                     }

                //                                     $amount = $record->$field ?? 0;
                //                                     if ($amount <= 0) {
                //                                         return '$0.00 (0%)';
                //                                     }

                //                                     $total = $record->total_amount - ($record->bonus_amount ?? 0);
                //                                     $percent = $total > 0 ? round(($amount / $total) * 100, 1) : 0;

                //                                     return '$'.number_format($amount, 2).' ('.$percent.'%)';
                //                                 });
                //                         }

                //                         return $components;
                //                     })
                //                     ->columns(3),
                //                 // Infolists\Components\TextEntry::make('total_policies_summary')
                //                 //     ->label('Total Policies Commission')
                //                 //     // ->content(function ($record) {
                //                 //     //     if (! $record || ! $record->exists) {
                //                 //     //         return 'N/A';
                //                 //     //     }

                //                 //     //     $total = $record->total_amount - ($record->bonus_amount ?? 0);

                //                 //     //     return '$'.number_format($total, 2);
                //                 //     // })
                //                 //     ->extraAttributes(['class' => 'text-lg font-bold']),
                //             ])
                //             ->collapsible(),
                //     ])
                //     ->columns(1),

                // Infolists\Components\Section::make('Bonus Information')
                //     ->schema([
                //         Infolists\Components\TextEntry::make('bonus_amount')
                //             ->label('Bonus Amount')
                //             ->numeric()
                //             ->prefix('$'),
                //         Infolists\Components\TextEntry::make('bonus_notes')
                //             ->label('Bonus Notes')
                //             ->columnSpanFull(),
                //     ])
                //     ->columns(2),
            ]);
    }
}
