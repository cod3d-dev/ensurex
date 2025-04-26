<?php

namespace App\Filament\Resources\IssueResource\Pages;

use App\Filament\Resources\IssueResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListIssues extends ListRecords
{
    protected static string $resource = IssueResource::class;

//    protected function getHeaderActions(): array
//    {
//        return [
//            Actions\CreateAction::make(),
//        ];
//    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'to_review' => Tab::make('Por Revisar')
                ->query(fn ($query) => $query->where('status', 'to_review'))
                ->badge(fn () => static::getModel()::where('status', 'to_review')->count()),
            'processing' => Tab::make('En Proceso')
                ->query(fn ($query) => $query->where('status', 'processing'))
                ->badge(fn () => static::getModel()::where('status', 'processing')->count()),
            'to_send' => Tab::make('Por Enviar')
                ->query(fn ($query) => $query->where('status', 'to_send'))
                ->badge(fn () => static::getModel()::where('status', 'to_send')->count()),
            'sent' => Tab::make('Enviado')
                ->query(fn ($query) => $query->where('status', 'sent'))
                ->badge(fn () => static::getModel()::where('status', 'sent')->count()),
            'resolved' => Tab::make('Resuelto')
                ->query(fn ($query) => $query->where('status', 'resolved'))
                ->badge(fn () => static::getModel()::where('status', 'resolved')->count()),
            'no_solution' => Tab::make('Sin Solución')
                ->query(fn ($query) => $query->where('status', 'no_solution'))
                ->badge(fn () => static::getModel()::where('status', 'no_solution')->count()),
        ];
    }
}
