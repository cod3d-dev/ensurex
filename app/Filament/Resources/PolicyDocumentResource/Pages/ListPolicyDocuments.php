<?php

namespace App\Filament\Resources\PolicyDocumentResource\Pages;

use App\Enums\DocumentStatus;
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
    ->query(fn ($query) =>
        $query->whereNot('status', DocumentStatus::Approved)
            ->where(function ($q) {
                $q->whereDate('due_date', '<', now())
                  ->orWhereNull('due_date');
            })
    )
    ->badge(fn () =>
        static::getModel()::whereNot('status', DocumentStatus::Approved)
            ->where(function ($q) {
                $q->whereDate('due_date', '<', now())
                  ->orWhereNull('due_date');
            })
            ->count()
    ),
            'today' => Tab::make('Vencen Hoy')
                ->query(fn ($query) => $query->whereNot('status', DocumentStatus::Approved)->whereDate('due_date', now()))
                ->badge(fn () => static::getModel()::whereNot('status', DocumentStatus::Approved)->whereDate('due_date', now())->count()),
            'tomorrow' => Tab::make('Vencen Mañana')
                ->query(fn ($query) => $query->whereNot('status', DocumentStatus::Approved)->whereDate('due_date', now()->addDay()))
                ->badge(fn () => static::getModel()::whereNot('status', DocumentStatus::Approved)->whereDate('due_date', now()->addDay())->count()),
            'this_week' => Tab::make('Vencen Esta Semana')
                ->query(fn ($query) => $query->whereNot('status', DocumentStatus::Approved)
                    ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
                )
                ->badge(fn () => static::getModel()::whereNot('status', DocumentStatus::Approved)
                    ->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count()
                ),

            'pending' => Tab::make('Pendientes')
                ->query(fn ($query) => $query->whereNot('status', DocumentStatus::Approved)->where('status', 'pending'))
                ->badge(fn () => static::getModel()::whereNot('status', DocumentStatus::Approved)->where('status', 'pending')->count()),
            'sent' => Tab::make('Enviados')
                ->query(fn ($query) => $query->where('status', 'sent'))
                ->badge(fn () => static::getModel()::where('status', 'sent')->count()),

            'all' => Tab::make('Todos'),

        ];
    }
}
