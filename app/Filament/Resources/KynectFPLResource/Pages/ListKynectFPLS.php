<?php

namespace App\Filament\Resources\KynectFPLResource\Pages;

use App\Filament\Resources\KynectFPLResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKynectFPLS extends ListRecords
{
    protected static string $resource = KynectFPLResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
