<?php


namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
enum Gender: string implements HasLabel
{
    case Male = 'male';
    case Female = 'female';

    public function getLabel(): string
    {
        return match ($this) {
            self::Male => 'Masculino',
            self::Female => 'Femenino',
        };
    }

}
