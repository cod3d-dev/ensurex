<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
;use App\Enums\Gender;
use App\Enums\ImmigrationStatus;
use App\Filament\Resources\PolicyResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;
use App\Enums\PolicyStatus;
use App\Enums\DocumentStatus;
use App\Enums\UsState;
use App\Enums\PolicyType;
use App\Models\Policy;




class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Miembros';

    protected static ?string $navigationIcon = 'carbon-pedestrian-family';

    public $readonly = true;


    
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if(!isset($data['main_applicant'])) {
            $data['main_applicant'] = [];
        }

        if(!isset($data['contact_information'])) {
            $data['contact_information'] = [];
        }

        $data['contact_information']['first_name'] = $data->contact->first_name ?? null;
        $data['contact_information']['middle_name'] = $data->contact->middle_name ?? null;
        $data['contact_information']['last_name'] = $data->contact->last_name ?? null;
        $data['contact_information']['second_last_name'] = $data->contact->second_last_name ?? null;

        $data['main_applicant']['fullname'] = $data['contact_information']['first_name'] . ' ' . $data['contact_information']['middle_name'] . $data['contact_information']['last_name'] . $data['contact_information']['second_last_name'];

        if ($data['policy_us_state'] === 'KY' ) {
            $data['requires_aca'] = true;
        }
        return $data;


    }

    

}
