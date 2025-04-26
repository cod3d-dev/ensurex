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
            'pending' => Tab::make('Pendiente')
                ->query(fn ($query) => $query->where('status', 'pending'))
                ->badge(fn () => static::getModel()::where('status', 'pending')->count()),
            'sent' => Tab::make('Enviada')
                ->query(fn ($query) => $query->where('status', 'sent'))
                ->badge(fn () => static::getModel()::where('status', 'sent')->count()),
            'accepted' => Tab::make('Aceptada')
                ->query(fn ($query) => $query->where('status', 'accepted'))
                ->badge(fn () => static::getModel()::where('status', 'accepted')->count()),
            'converted' => Tab::make('Convertida')
                ->query(fn ($query) => $query->where('status', 'converted'))
                ->badge(fn () => static::getModel()::where('status', 'converted')->count()),
            'rejected' => Tab::make('Rechazada')
                ->query(fn ($query) => $query->where('status', 'rejected'))
                ->badge(fn () => static::getModel()::where('status', 'rejected')->count()),
            'all' => Tab::make('Todas')
                ->badge(fn () => static::getModel()::count()),
        ];
    }
}
