<?php

namespace App\Filament\Resources\KynectFPLResource\Pages;

use App\Filament\Resources\KynectFPLResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKynectFPL extends EditRecord
{
    protected static string $resource = KynectFPLResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
