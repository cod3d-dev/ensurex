<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IssueTypeResource\Pages;
use App\Models\IssueType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IssueTypeResource extends Resource
{
    protected static ?string $model = IssueType::class;

    protected static ?string $navigationGroup = 'Ajustes';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Tipos de Problemas';

    protected static ?string $modelLabel = 'Problema';

    protected static ?string $pluralModelLabel = 'Problemas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('verification_days')
                    ->required()
                    ->numeric()
                    ->default(5),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('verification_days')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssueTypes::route('/'),
            'create' => Pages\CreateIssueType::route('/create'),
            'edit' => Pages\EditIssueType::route('/{record}/edit'),
        ];
    }
}
