<?php

namespace App\Filament\Resources\PolicyDocumentResource\Pages;

use App\Filament\Resources\PolicyDocumentResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPolicyDocuments extends ListRecords
{
    protected static string $resource = PolicyDocumentResource::class;

    protected static ?string $title = 'Documentos';

    //    protected function getHeaderActions(): array
    //    {
    //        return [
    //            Actions\CreateAction::make(),
    //        ];
    //    }

    public function getTabs(): array
    {
        return [
            'expired' => Tab::make('Vencidos')
                ->query(fn ($query) => $query->where('status', 'expired')->orWhere('status', 'pending')->whereDate('due_date', '<', now()))
                ->badge(fn () => static::getModel()::where('status', 'expired')->orWhere('status', 'pending')->whereDate('due_date', '<', now())->count()),
            'today' => Tab::make('Vencen Hoy')
                ->query(fn ($query) => $query->where('status', 'pending')->whereDate('due_date', now()))
                ->badge(fn () => static::getModel()::where('status', 'pending')->whereDate('due_date', now())->count()),
            'tomorrow' => Tab::make('Vencen Mañana')
                ->query(fn ($query) => $query->where('status', 'pending')->whereDate('due_date', now()->addDay()))
                ->badge(fn () => static::getModel()::where('status', 'pending')->whereDate('due_date', now()->addDay())->count()),
            'this_week' => Tab::make('Vencen Esta Semana')
                ->query(fn ($query) => $query->where('status', 'pending')->whereDate('due_date', '>=', now())->whereDate('due_date', '<=', now()->endOfWeek()))
                ->badge(fn () => static::getModel()::where('status', 'pending')->whereDate('due_date', '>=', now())->whereDate('due_date', '<=', now()->endOfWeek())->count()),

            'pending' => Tab::make('Pendientes')
                ->query(fn ($query) => $query->where('status', 'pending'))
                ->badge(fn () => static::getModel()::where('status', 'pending')->count()),
            'sent' => Tab::make('Enviados')
                ->query(fn ($query) => $query->where('status', 'sent'))
                ->badge(fn () => static::getModel()::where('status', 'sent')->count()),

            'all' => Tab::make('Todos'),

        ];
    }
}
