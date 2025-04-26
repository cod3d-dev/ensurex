<?php

namespace App\Casts;

use App\ValueObjects\ApplicantCollection;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class ApplicantCollectionCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return new ApplicantCollection;
        }

        $data = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new ApplicantCollection;
        }

        return ApplicantCollection::fromArray($data);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof ApplicantCollection) {
            return json_encode($value->toArray());
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        throw new InvalidArgumentException('The given value is not an ApplicantCollection instance.');
    }
}
