<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
enum FamilyRelationship: string implements HasLabel
{
    case Self = 'self';
    case Spouse = 'spouse';
    case Father = 'father';
    case Son = 'son';
    case Concubine = 'concubine';
    case Nephew = 'nephew';
    case Stepson = 'stepson';
    case FosterChild = 'foster_child';
    case ExSpouse = 'ex_spouse';
    case Other = 'other';

    public function getLabel(): string
    {
        return match($this) {
            self::Self => 'Aplicante Principal',
            self::Spouse => 'Esposo o Esposa',
            self::Father => 'Padre o Madre',
            self::Son => 'Hijo',
            self::Concubine => 'Concubino',
            self::Nephew => 'Sobrino',
            self::Stepson => 'Hijastro',
            self::FosterChild => 'Hijo adoptivo',
            self::ExSpouse => 'Ex-Esposo',
            self::Other => 'Otro',
        };
    }
}
