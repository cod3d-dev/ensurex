<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\PolicyDocumentResource;
use App\Filament\Resources\PolicyResource;
use App\Models\PolicyDocument;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestDocs extends BaseWidget
{
    protected static ?string $heading = 'Documentos Pendientes';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => PolicyDocument::query()->whereIn('status', [
                    \App\Enums\DocumentStatus::ToAdd->value,
                    \App\Enums\DocumentStatus::Pending->value,
                    \App\Enums\DocumentStatus::Rejected->value,
                ]),
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->color(function (\App\Enums\DocumentStatus $state): string {
                        return match ($state->value) {
                            \App\Enums\DocumentStatus::ToAdd->value => 'warning',
                            \App\Enums\DocumentStatus::Pending->value => 'info',
                            \App\Enums\DocumentStatus::Rejected->value => 'danger',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->dateTime('m/d/Y'),
            ])
            // ->actions([
            //     Tables\Actions\Action::make('abrir')
            //         ->url(fn (PolicyDocument $record): string => PolicyDocumentResource::getUrl('view', ['record' => $record])),
            // ])
            ->headerActions([
                Tables\Actions\Action::make('view_quotes')
                    ->label('Ver')
                    ->url(fn (): string => PolicyDocumentResource::getUrl('index')),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated(['5'])
            ->recordUrl(
                fn (PolicyDocument $record): string => PolicyResource::getUrl('documents', ['record' => $record->policy_id]),
            );
    }
}
