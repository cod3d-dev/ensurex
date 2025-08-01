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
                ])->orderBy('updated_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->wrap()
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
                    ->sortable()
                    ->html()
                    ->formatStateUsing(function (Issue $record, string $state): string {
                        $policy = $record->policy;
                        $documentName = e($state);

                        if (! $policy) {
                            return '<span>'.$documentName.'</span>';
                        }

                        $contact = $policy->contact;
                        $contactName = $contact ? e($contact->full_name) : 'No Contact';
                        $policyCode = e($policy->code);

                        $detailsHtml = '<div class="text-xs text-gray-500">'.$policyCode.' - '.$contactName.'</div>';

                        return '<div>'.$documentName.'</div><hr class="my-1 border-gray-300">'.$detailsHtml;
                    }),
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
