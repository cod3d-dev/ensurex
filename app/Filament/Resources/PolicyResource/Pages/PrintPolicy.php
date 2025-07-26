<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\PolicyStatus;
use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class PrintPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;
    
    protected static string $view = 'filament.resources.policy.print';
    
    protected ?string $heading = '';
    
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];
        $breadcrumbs['/policies'] = 'Pólizas';
        $breadcrumbs['/policies/' . $this->record->id] = 'Póliza #' . $this->record->code;
        $breadcrumbs[] = 'Imprimir';
        
        return $breadcrumbs;
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action('print')
                ->hidden(fn () => ! $this->record->canBePrinted()),
        ];
    }
    
    public function print(): void
    {
        $this->js('window.print()');
    }
}
