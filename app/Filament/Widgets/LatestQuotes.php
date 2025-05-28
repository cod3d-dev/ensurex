<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\QuoteResource;
use App\Models\Quote;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestQuotes extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $heading = 'Cotizaciones Pendientes';

    public function table(Table $table): Table
    {

        return $table
            ->query(
                fn () => Quote::query()->whereIn('status', [
                    \App\Enums\QuoteStatus::Pending->value,
                    \App\Enums\QuoteStatus::Sent->value,
                    \App\Enums\QuoteStatus::Accepted->value,
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
                    ->color(function (\App\Enums\QuoteStatus $state): string {
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
            ->actions([
                Tables\Actions\Action::make('abrir')
                    ->url(fn (Quote $record): string => QuoteResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('created_at', 'asc');
    }
}
