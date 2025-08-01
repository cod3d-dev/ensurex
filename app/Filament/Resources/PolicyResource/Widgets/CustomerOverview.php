<?php

namespace App\Filament\Resources\PolicyResource\Widgets;

use App\Models\Policy;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CustomerOverview extends BaseWidget
{
    public ?Policy $record = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                fn () => Policy::query()->where('id', $this->record->id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('contact.full_name')
                    ->label('Nombre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact.email')
                    ->label('Email')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contact.phone')
                    ->label('Telefono')
                    ->sortable(),
            ]);
    }
}
