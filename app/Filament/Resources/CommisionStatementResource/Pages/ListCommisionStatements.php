<?php

namespace App\Filament\Resources\CommisionStatementResource\Pages;

use App\Filament\Resources\CommisionStatementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommisionStatements extends ListRecords
{
    protected static string $resource = CommisionStatementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
