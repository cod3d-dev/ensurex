<?php

namespace App\Filament\Resources\CommisionStatementResource\Pages;

use App\Filament\Resources\CommisionStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCommisionStatement extends EditRecord
{
    protected static string $resource = CommisionStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
