<?php

namespace App\Casts;

use App\ValueObjects\Applicant;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class ApplicantCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return Applicant::fromArray($data);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Applicant) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        throw new InvalidArgumentException('The given value is not an Applicant instance.');
    }
}
