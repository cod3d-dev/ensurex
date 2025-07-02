<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->simplePaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }

    public function getTabs(): array
    {
        // pending', 'active', 'inactive', 'cancelled', 'expired'
        return [
            'pending' => Tab::make('Pendientes')
                ->query(fn ($query) => $query->whereIn('status', ['pending']))
                ->badge(fn () => static::getModel()::whereIn('status', ['pending'])->count()),
            'sent' => Tab::make('Enviadas')
                ->query(fn ($query) => $query->where('status', 'sent'))
                ->badge(fn () => static::getModel()::where('status', 'sent')->count()),
            'accepted' => Tab::make('Aceptadas')
                ->query(fn ($query) => $query->where('status', 'accepted'))
                ->badge(fn () => static::getModel()::where('status', 'accepted')->count()),
            'converted' => Tab::make('Convertidas')
                ->query(fn ($query) => $query->where('status', 'converted'))
                ->badge(fn () => static::getModel()::where('status', 'converted')->count()),
            'rejected' => Tab::make('Rechazadas')
                ->query(fn ($query) => $query->where('status', 'rejected'))
                ->badge(fn () => static::getModel()::where('status', 'rejected')->count()),
            'all' => Tab::make('Todas')
                ->badge(fn () => static::getModel()::count()),
        ];
    }
}
