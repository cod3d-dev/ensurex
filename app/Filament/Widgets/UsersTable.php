<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\User;
use App\Filament\Widgets\UsersTrend;

class UsersTable extends BaseWidget
{
    public int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => User::query()  
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('Cotizaciones')
                    ->searchable(),
                Tables\Columns\TextColumn::make('id_2')
                    ->label('Polizas')
                    ->formatStateUsing(fn (User $record) => $record->id)
                    ->searchable(),
                
                
            ]);
    }
}
