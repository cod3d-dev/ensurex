<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Enums\Gender;
use App\Enums\UsState;

class ViewPolicyCompact extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected static string $view = 'filament.resources.policy.print';
    protected ?string $heading = '';

    public ?array $data = [];

    public $mode = 'view';


}
