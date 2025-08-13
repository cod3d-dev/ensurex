<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CommissionStatementStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Generated => 'primary',
            self::Paid => 'success',
            self::Cancelled => 'danger',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Generated => 'Generado',
            self::Paid => 'Pagado',
            self::Cancelled => 'Cancelado',
        };
    }
}
