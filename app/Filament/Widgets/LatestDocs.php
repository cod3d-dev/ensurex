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
                fn () => PolicyDocument::query()->whereNotIn('status', [
                    \App\Enums\DocumentStatus::Approved,
                ])->where('due_date', '<=', now()->addDays(5)->toDateString())->orderBy('due_date', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->html()
                    ->formatStateUsing(function (PolicyDocument $record, string $state): string {
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
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge(),
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
