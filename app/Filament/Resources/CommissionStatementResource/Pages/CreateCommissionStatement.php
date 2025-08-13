<?php

namespace App\Filament\Resources\CommissionStatementResource\Pages;

use App\Filament\Resources\CommissionStatementResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class CreateCommissionStatement extends CreateRecord implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = CommissionStatementResource::class;
}
