<?php


namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
enum MaritialStatus: string implements HasLabel
{
    case Single = 'single';
    case Married = 'married';
    case Divorced = 'divorced';
    case Widowed = 'widowed';
    case Separated = 'separated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Single => 'Soltero',
            self::Married => 'Casado',
            self::Divorced => 'Divorciado',
            self::Widowed => 'Viudo',
            self::Separated => 'Separado',
        };
    }

}
