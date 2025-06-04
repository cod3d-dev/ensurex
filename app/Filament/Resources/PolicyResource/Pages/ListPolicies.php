<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

class ListPolicies extends ListRecords
{
    protected static string $resource = PolicyResource::class;

    use ExposesTableToWidgets;

    protected function getHeaderWidgets(): array
    {
        return PolicyResource::getWidgets();
    }

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
                ->query(fn ($query) => $query->whereIn('status', ['pending', 'draft']))
                ->badge(fn () => static::getModel()::whereIn('status', ['pending', 'draft'])->count()),
            'past_year' => Tab::make(date('Y', strtotime('-1 year')))
                ->query(fn ($query) => $query->whereYear('effective_date', date('Y', strtotime('-1 year'))))
                ->badge(fn () => static::getModel()::whereYear('effective_date', date('Y', strtotime('-1 year')))->count()),
            'this_year' => Tab::make(date('Y'))
                ->query(fn ($query) => $query->whereYear('effective_date', date('Y')))
                ->badge(fn () => static::getModel()::whereYear('effective_date', date('Y'))->count()),

            'active' => Tab::make('Activa')
                ->query(fn ($query) => $query->where('status', 'active'))
                ->badge(fn () => static::getModel()::where('status', 'active')->count()),
            'inactive' => Tab::make('Inactiva')
                ->query(fn ($query) => $query->where('status', 'inactive'))
                ->badge(fn () => static::getModel()::where('status', 'inactive')->count()),
            'cancelled' => Tab::make('Cancelada')
                ->query(fn ($query) => $query->where('status', 'cancelled'))
                ->badge(fn () => static::getModel()::where('status', 'cancelled')->count()),
            'expired' => Tab::make('Vencida')
                ->query(fn ($query) => $query->where('status', 'expired'))
                ->badge(fn () => static::getModel()::where('status', 'expired')->count()),
            'all' => Tab::make('Todos')
                ->badge(fn () => static::getModel()::count()),
        ];
    }
}
