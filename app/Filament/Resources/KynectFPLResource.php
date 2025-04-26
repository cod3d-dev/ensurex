<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KynectFPLResource\Pages;
use App\Filament\Resources\KynectFPLResource\RelationManagers;
use App\Models\KynectFPL;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KynectFPLResource extends Resource
{
    protected static ?string $model = KynectFPL::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Ajustes';
   protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Kynect FPL';
    protected static ?string $modelLabel = 'Kynect FPL';
    protected static ?string $pluralModelLabel = 'Kynect FPL';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('year')
                    ->required(),
                Forms\Components\TextInput::make('members_1')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('members_2')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('members_3')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('members_4')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('members_5')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('members_6')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('members_7')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('members_8')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('additional_member')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year'),
                Tables\Columns\TextColumn::make('members_1')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_2')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_3')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_4')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_5')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_6')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_7')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('members_8')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('additional_member')
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
            'index' => Pages\ListKynectFPLS::route('/'),
            'create' => Pages\CreateKynectFPL::route('/create'),
            'edit' => Pages\EditKynectFPL::route('/{record}/edit'),
        ];
    }
}
