<?php

namespace App\ValueObjects;

use Illuminate\Support\Collection;

class ApplicantCollection extends Collection
{
    public function __construct($items = [])
    {
        parent::__construct($items);
    }

    public static function fromArray(array $data): self
    {
        $applicants = collect($data)->map(function ($applicantData, $index) {
            return Applicant::fromArray($applicantData, $index === 0);
        });

        return new self($applicants);
    }




    public function isCompleteForQuote(): bool
    {
        return $this->every(function (Applicant $applicant) {
            return $applicant->isCompleteForQuote();
        });
    }

    public function isCompleteForPolicy(): bool
    {
        return $this->every(function (Applicant $applicant) {
            return $applicant->isCompleteForPolicy();
        });
    }

    public function getMissingPolicyFields(): array
    {
        return $this->mapWithKeys(function (Applicant $applicant, $key) {
            $missingFields = $applicant->getMissingPolicyFields();
            return $missingFields ? [$key => $missingFields] : [];
        })->filter()->all();
    }

    public function toArray(): array
    {
        return $this->map->toArray()->values()->all();
    }
}
