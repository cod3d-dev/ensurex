<?php

namespace App\Filament\Resources\CommissionStatementResource\Pages;

use App\Filament\Resources\CommissionStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommissionStatement extends EditRecord
{
    protected static string $resource = CommissionStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
