<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolicyTypeResource\Pages;
use App\Filament\Resources\PolicyTypeResource\RelationManagers;
use App\Models\PolicyType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PolicyTypeResource extends Resource
{
    protected static ?string $model = PolicyType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationGroup = 'Ajustes';
   protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Tipos de Polizas';
    protected static ?string $modelLabel = 'Tipo de Poliza';
    protected static ?string $pluralModelLabel = 'Tipos de Polizas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
                Forms\Components\Select::make('color')
                    ->options([
                        'success' => 'success',
                        'primary' => 'primary',
                        'danger' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        'secondary' => 'secondary',
                        'light' => 'light',
                        'dark' => 'dark',
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
            'index' => Pages\ListPolicyTypes::route('/'),
            'create' => Pages\CreatePolicyType::route('/create'),
            'edit' => Pages\EditPolicyType::route('/{record}/edit'),
        ];
    }
}
