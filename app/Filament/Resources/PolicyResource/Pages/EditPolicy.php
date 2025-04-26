<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class EditPolicy extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Poliza';
    protected static ?string $navigationIcon = 'iconoir-privacy-policy';

    public static string|\Filament\Support\Enums\Alignment $formActionsAlignment = 'end';


    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

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
