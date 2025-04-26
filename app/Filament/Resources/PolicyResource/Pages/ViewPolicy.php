<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Contact;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Poliza';
    protected static ?string $navigationIcon = 'iconoir-privacy-policy';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }

}
