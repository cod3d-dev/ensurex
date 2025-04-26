<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RenewalStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::Pending => 'Pendiente',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
