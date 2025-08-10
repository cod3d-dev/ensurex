<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\User;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Carbon\Carbon;

class UsersTable extends BaseWidget
{
    use InteractsWithPageFilters;
    
    // Properties to store total counts
    public int $totalQuotes = 0;
    public int $totalPolicies = 0;
    public int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        $startDate = ! is_null($this->filters['startDate'] ?? null) ?
        Carbon::parse($this->filters['startDate']) :
        null;

        $endDate = ! is_null($this->filters['endDate'] ?? null) ?
            Carbon::parse($this->filters['endDate'])->endOfDay() :
            now()->endOfDay();
        
        // Get total counts for the selected period
        $totalQuotes = \App\Models\Quote::query()
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate))
            ->count();
            
        $totalPolicies = \App\Models\Policy::query()
            ->when($startDate, fn($q) => $q->where('created_at', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('created_at', '<=', $endDate))
            ->count();
            
        // Build query with efficient database aggregations
        $query = User::query()
            ->select(['users.*'])
            ->selectSub(function ($query) use ($startDate, $endDate) {
                $query->selectRaw('COUNT(*)')
                    ->from('quotes')
                    ->whereColumn('quotes.user_id', 'users.id')
                    ->when($startDate, fn($q) => $q->where('quotes.created_at', '>=', $startDate))
                    ->when($endDate, fn($q) => $q->where('quotes.created_at', '<=', $endDate));
            }, 'quotes_count')
            ->selectSub(function ($query) use ($startDate, $endDate) {
                $query->selectRaw('COUNT(*)')
                    ->from('policies')
                    ->whereColumn('policies.user_id', 'users.id')
                    ->when($startDate, fn($q) => $q->where('policies.created_at', '>=', $startDate))
                    ->when($endDate, fn($q) => $q->where('policies.created_at', '<=', $endDate));
            }, 'policies_count')
            ->orderByDesc('policies_count')
            ->orderByDesc('quotes_count');

        // Store total counts for use in the table
        $this->totalQuotes = $totalQuotes;
        $this->totalPolicies = $totalPolicies;
        
        return $table
            ->query($query)
            ->defaultSort('policies_count', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('Posición')
                    ->state(function ($record, $rowLoop): string {
                        return $rowLoop->iteration;
                    })
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')   
                    ->searchable(),
                Tables\Columns\TextColumn::make('quotes_count')
                    ->label('Cotizaciones')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quotes_percentage')
                    ->label('% Cotizaciones')
                    ->state(function ($record): string {
                        if ($this->totalQuotes === 0) {
                            return '0.0%';
                        }
                        $percentage = ($record->quotes_count / $this->totalQuotes) * 100;
                        return number_format($percentage, 1) . '%';
                    }),
                Tables\Columns\TextColumn::make('policies_count')
                    ->label('Polizas')
                    ->sortable(),
                Tables\Columns\TextColumn::make('policies_percentage')
                    ->label('% Polizas')
                    ->state(function ($record): string {
                        if ($this->totalPolicies === 0) {
                            return '0.0%';
                        }
                        $percentage = ($record->policies_count / $this->totalPolicies) * 100;
                        return number_format($percentage, 1) . '%';
                    }),
            ]);
    }
}
