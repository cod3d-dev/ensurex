<?php


namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;




enum PolicyStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Active = 'active';
    case Inactive = 'inactive';
    case Cancelled = 'cancelled';


    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
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
            self::Draft => 'info',
            self::Pending => 'warning',
            self::Rejected => 'danger',
            self::Active => 'success',
            self::Inactive => 'warning',
            self::Cancelled => 'danger',
        };
    }
}
