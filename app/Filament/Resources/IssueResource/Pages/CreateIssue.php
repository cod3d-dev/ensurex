<?php

namespace App\Filament\Resources\IssueResource\Pages;

use App\Filament\Resources\IssueResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateIssue extends CreateRecord
{
    protected static string $resource = IssueResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        dd($data);
        $newNote = $data('new_note') ?? null;
        if(isset($newNote)) {
            $data['notes'] = $data['notes'] . "\n" . auth()->user()->name . ': ' . now()->toDateTimeString() . "\n" . $newNote . "\n";
        }
        unset($data['new_note']);
        return $data;
    }
}
