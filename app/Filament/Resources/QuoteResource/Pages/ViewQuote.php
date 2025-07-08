<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Policy;
use App\Models\Quote;
use App\Services\QuoteConversionService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->hidden(function (Quote $record) {
                    return $record->status->value == 'converted';
                })
                ->color('warning'),
            Actions\Action::make('convert_to_policy')
                ->label('Crear Polizas')
                ->icon('heroicon-o-document-duplicate')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(function (Quote $record) {
                    return $record->status->value == 'converted';
                })
                ->modalHeading('Crear Poliza')
                ->modalDescription('Se creara una poliza a partir de esta cotización.')
                ->modalSubmitActionLabel('Crear y Editar')
                ->action(function (Quote $record, array $data) {
                    // Use the QuoteConversionService to handle the conversion logic
                    $conversionService = app(QuoteConversionService::class);
                    $policy = $conversionService->convertQuoteToPolicy($record, $data);

                    // Redirect to edit the policy
                    $this->redirect(PolicyResource::getUrl('edit', ['record' => $policy->id]));
                }),
        ];
    }
}
