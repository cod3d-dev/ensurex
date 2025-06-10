<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PolicyInscriptionType: string implements HasColor, HasLabel
{
    case OpenEnrollment = 'open_enrollment';
    case SEP150 = 'sep_150';
    case SEPMedicaid = 'sep_medicaid';
    case SEPLawfulStatus = 'sep_lawful_status';
    case SEPAddressChange = 'sep_change_of_address';
    case SEPLossOfInsurance = 'sep_loss_of_insurance';
    case SEPMarried = 'sep_married';
    case SEPNNewborn = 'sep_newborn';
    case SEPDDeath = 'sep_death';

    public function getLabel(): string
    {
        return match ($this) {
            self::OpenEnrollment => 'Open Enrollment',
            self::SEP150 => 'SEP: 150%',
            self::SEPMedicaid => 'SEP: Pérdida de Medicaid',
            self::SEPLawfulStatus => 'SEP: Cambio en el estatus legal',
            self::SEPAddressChange => 'SEP: Cambio de dirección',
            self::SEPLossOfInsurance => 'SEP: Pérdida de Seguro',
            self::SEPMarried => 'SEP: Casado',
            self::SEPNNewborn => 'SEP: Nuevo Nacido',
            self::SEPDDeath => 'SEP: Muerte de miembro',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::OpenEnrollment => 'info',
            self::SEP150 => 'success',
            self::SEPMedicaid => 'warning',
            self::SEPLawfulStatus => 'violet',
            self::SEPAddressChange => 'danger',
            self::SEPLossOfInsurance => 'info',
            self::SEPMarried => 'success',
            self::SEPNNewborn => 'warning',
            self::SEPDDeath => 'violet',
        };
    }
}
