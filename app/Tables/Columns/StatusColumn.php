<?php

namespace App\Tables\Columns;

use Filament\Tables\Columns\Column;
use App\Enums\DocumentStatus;

class StatusColumn extends Column
{
    protected string $view = 'tables.columns.status-column';
}
