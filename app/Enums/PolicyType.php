<?php


namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;




enum PolicyType: string implements HasLabel, HasColor
{
    case Health = 'health';
    case Life = 'life';
    case Vision = 'vision';
    case Dental = 'dental';
    

    public function getLabel(): string
    {
        return match ($this) {
            self::Health => 'Salud',
            self::Life => 'Vida',
            self::Vision => 'Vision',
            self::Dental => 'Dental',

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Health => 'info',
            self::Life => 'success',
            self::Vision => 'warning',
            self::Dental => 'violet',
        };
    }
}
