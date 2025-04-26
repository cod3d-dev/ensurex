<?php

namespace App\Filament\Resources\PolicyTypeResource\Pages;

use App\Filament\Resources\PolicyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolicyTypes extends ListRecords
{
    protected static string $resource = PolicyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
