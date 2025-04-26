<?php


namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;


enum IssueStatus: string implements HasLabel, HasColor
{
    case ToReview = 'to_review';
    case Processing = 'processing';
    case ToSend = 'to_send';
    case Sent = 'sent';
    case Resolved = 'resolved';
    case NoSolution = 'no_solution';


    public function getLabel(): string
    {
        return match ($this) {
            self::ToReview => 'Por Revisar',
            self::Processing => 'En Proceso',
            self::ToSend => 'Por Enviar',
            self::Sent => 'Enviado',
            self::Resolved => 'Resuelto',
            self::NoSolution => 'Sin Solución',

        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ToReview => 'info',
            self::Processing => 'warning',
            self::ToSend => 'warning',
            self::Sent => 'info',
            self::Resolved => 'success',
            self::NoSolution => 'danger',
        };
    }
}
