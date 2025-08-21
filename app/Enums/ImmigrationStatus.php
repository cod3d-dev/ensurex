<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ImmigrationStatus: string implements HasLabel
{
    case Citizen = 'citizen';
    case Resident = 'resident';
    case WorkPermit = 'work_permit';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Citizen => 'Ciudadano',
            self::Resident => 'Residente Permanente',
            self::WorkPermit => 'Permiso de trabajo',
            self::Other => 'Otro',
        };
    }
}
