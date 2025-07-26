<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PolicyStatus: string implements HasColor, HasLabel
{
    case ToVerify = 'to_verify';
    case Draft = 'draft';

    case Created = 'created';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Active = 'active';
    case Inactive = 'inactive';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::ToVerify => 'Por Verificar',
            self::Draft => 'Borrador',
            self::Created => 'Creada',
            self::Pending => 'En Proceso',
            self::Rejected => 'Rechazada',
            self::Active => 'Activa',
            self::Inactive => 'Inactiva',
            self::Cancelled => 'Terminada',

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ToVerify => 'danger',
            self::Draft => 'warning',
            self::Created => 'info',
            self::Pending => 'warning',
            self::Rejected => 'danger',
            self::Active => 'success',
            self::Inactive => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
