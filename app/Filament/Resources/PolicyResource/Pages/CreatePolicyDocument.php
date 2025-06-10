<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePolicyDocument extends CreateRecord
{
    protected static string $resource = PolicyDocumentResource::class;

    protected function beforeSave(): void
    {
        dd($this->getOwnerRecord());
    }
}
