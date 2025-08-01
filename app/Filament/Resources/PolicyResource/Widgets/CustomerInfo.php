<?php

namespace App\Filament\Resources\PolicyResource\Widgets;

use App\Models\Policy;
use Filament\Widgets\Widget;

class CustomerInfo extends Widget
{
    public ?Policy $record = null;

    protected int|string|array $columnSpan = 'full';

    protected static string $view = 'filament.resources.policy-resource.widgets.customer-info';

    public function mount(Policy $record)
    {
        $this->contact = $record->contact->full_name;
    }
}
