<?php

namespace App\Filament\Resources\QuoteResource\Actions;

use App\Enums\DocumentStatus;
use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Enums\QuoteStatus;
use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use App\Services\QuoteConversionService;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class ConvertToPolicy extends Action
{
    protected ?Policy $policy = null;

    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('Crear Polizas')
            ->icon('heroicon-o-document-duplicate')
            ->color('success')
            ->action(function (ConvertToPolicy $action, Model $record): void {
                // Use the QuoteConversionService to handle the conversion logic
                $conversionService = app(QuoteConversionService::class);
                $action->policy = $conversionService->convertQuoteToPolicy($record);
            })
            ->after(function (ConvertToPolicy $action): void {
                $action->success();
                $action->redirect(PolicyResource::getUrl('edit', ['record' => $action->policy]));
            })
            ->requiresConfirmation()
            ->modalHeading('Crear Poliza')
            ->modalDescription('Se creara una poliza a partir de esta cotización.')
            ->modalSubmitActionLabel('Crear y Editar');
    }
}
