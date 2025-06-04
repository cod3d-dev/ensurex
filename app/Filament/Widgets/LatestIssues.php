<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\IssueResource;
use App\Filament\Resources\PolicyResource;
use App\Models\Issue;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestIssues extends BaseWidget
{
    protected static ?string $heading = 'Casos Pendientes';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => Issue::query()->whereIn('status', [
                    \App\Enums\IssueStatus::ToReview->value,
                    \App\Enums\IssueStatus::Processing->value,
                    \App\Enums\IssueStatus::ToSend->value,
                ]),
            )
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->color(function (\App\Enums\IssueStatus $state): string {
                        return match ($state->value) {
                            \App\Enums\IssueStatus::ToReview->value => 'warning',
                            \App\Enums\IssueStatus::Processing->value => 'info',
                            \App\Enums\IssueStatus::ToSend->value => 'danger',
                            default => 'gray',
                        };
                    }),
                Tables\Columns\TextColumn::make('policy.contact.full_name')
                    ->label('Cliente')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('view_issues')
                    ->label('Ver')
                    ->url(fn (): string => IssueResource::getUrl('index')),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated(['5'])
            ->recordUrl(
                fn (Issue $record): string => PolicyResource::getUrl('issues', ['record' => $record->policy_id]),
            );
    }
}
