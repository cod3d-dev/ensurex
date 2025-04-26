<?php

namespace App\Filament\Resources\PolicyTypeResource\Pages;

use App\Filament\Resources\PolicyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolicyType extends EditRecord
{
    protected static string $resource = PolicyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
