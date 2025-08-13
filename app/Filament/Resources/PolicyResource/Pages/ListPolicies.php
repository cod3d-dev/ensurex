<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\PolicyStatus;
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
            'pending' => Tab::make('Pendientes')
                ->query(fn ($query) => $query->whereIn('status', ['pending', 'draft', 'created']))
                ->badge(fn () => static::getModel()::whereIn('status', ['pending', 'draft', 'created'])->count()),
            'active' => Tab::make('Activa')
                ->query(fn ($query) => $query->where('status', PolicyStatus::Active->value))
                ->badge(fn () => static::getModel()::where('status', PolicyStatus::Active->value)->count()),
            'rejected' => Tab::make('Rechazada')
                ->query(fn ($query) => $query->where('status', PolicyStatus::Rejected->value))
                ->badge(fn () => static::getModel()::where('status', PolicyStatus::Rejected->value)->count()),
            'inactive' => Tab::make('Inactiva')
                ->query(fn ($query) => $query->where('status', PolicyStatus::Inactive->value))
                ->badge(fn () => static::getModel()::where('status', PolicyStatus::Inactive->value)->count()),
            'cancelled' => Tab::make('Cancelada')
                ->query(fn ($query) => $query->where('status', PolicyStatus::Cancelled->value))
                ->badge(fn () => static::getModel()::where('status', PolicyStatus::Cancelled->value)->count()),
            'past_year' => Tab::make(date('Y', strtotime('-1 year')))
                ->query(fn ($query) => $query->whereYear('effective_date', date('Y', strtotime('-1 year'))))
                ->badge(fn () => static::getModel()::whereYear('effective_date', date('Y', strtotime('-1 year')))->count()),
            'this_year' => Tab::make(date('Y'))
                ->query(fn ($query) => $query->whereYear('effective_date', date('Y')))
                ->badge(fn () => static::getModel()::whereYear('effective_date', date('Y'))->count()),

            'all' => Tab::make('Todas')
                ->badge(fn () => static::getModel()::count()),
        ];
    }
}
