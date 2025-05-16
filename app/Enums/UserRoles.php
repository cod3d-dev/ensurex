<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;



enum UserRoles: string implements HasLabel, HasColor
{
    case Admin = 'admin';
    case Agent = 'agent';
    case Supervisor = 'supervisor';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Agent => 'Agente',
            self::Supervisor => 'Supervisor',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Agent => 'success',
            self::Supervisor => 'warning',
        };
    }

    // Function to check if the user is an admin
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    // Function to check if the user is a supervisor
    public function isSupervisor(): bool
    {
        return $this === self::Supervisor;
    }

}
