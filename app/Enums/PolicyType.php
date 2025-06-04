<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PolicyType: string implements HasColor, HasLabel
{
    case Health = 'health';
    case Life = 'life';
    case Vision = 'vision';
    case Dental = 'dental';
    case Accident = 'accident';

    public function getLabel(): string
    {
        return match ($this) {
            self::Health => 'Salud',
            self::Life => 'Vida',
            self::Vision => 'Vision',
            self::Dental => 'Dental',
            self::Accident => 'Accidente',

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Health => 'info',
            self::Life => 'success',
            self::Vision => 'warning',
            self::Dental => 'violet',
            self::Accident => 'danger',
        };
    }
}
