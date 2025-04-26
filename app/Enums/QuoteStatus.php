<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;


enum QuoteStatus: string implements HasLabel, HasColor
{

    case Pending = 'pending';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Converted = 'converted';


    public function getLabel(): ?string
    {
        return match($this) {
            self::Pending => 'Pendiente',
            self::Sent => 'Enviada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
            self::Converted => 'Convertida',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Sent => 'blue',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::Converted => 'green',
        };
    }
}
