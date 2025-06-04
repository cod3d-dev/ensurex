<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestPolicies extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static ?string $heading = 'Polizas Pendientes';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => Policy::query()->whereIn('status', [
                    \App\Enums\PolicyStatus::Draft->value,
                    \App\Enums\PolicyStatus::Pending->value,
                ]),
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('m/d/Y'),
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Cliente')
                    ->sortable()
                    ->description(fn ($record) => 'Applicantes: '.$record->total_applicants),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->color(function (\App\Enums\PolicyStatus $state): string {
                        return match ($state->value) {
                            'pending' => 'warning',
                            'sent' => 'info',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            'converted' => 'success',
                            default => 'gray',
                        };
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_policies')
                    ->label('Ver')
                    ->url(fn (): string => PolicyResource::getUrl('index')),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated(['5'])
            ->recordUrl(
                fn (Policy $record): string => PolicyResource::getUrl('view', ['record' => $record]),
            );
    }
}
