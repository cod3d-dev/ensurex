<?php

namespace App\Filament\Resources\CommissionStatementResource\Pages;

use App\Filament\Resources\CommissionStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommissionStatements extends ListRecords
{
    protected static string $resource = CommissionStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate')
                ->label('Generar Comisiones')
                ->action(function () {
                    $this->redirect(CommissionStatementResource::getUrl('generate'));
                }),
        ];
    }
}
