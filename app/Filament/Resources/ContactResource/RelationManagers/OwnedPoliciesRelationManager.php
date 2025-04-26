<?php

namespace App\Filament\Resources\ContactResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\PolicyType;

class OwnedPoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'ownedPolicies';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('policy_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('policy_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('policy_year')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('policy_us_state')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('policy_type')
                    ->required()
                    ->options(PolicyType::class),
                Forms\Components\TextInput::make('insurance_company_policy_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID'),
                Tables\Columns\TextColumn::make('policy_type')
                    ->label('Tipo'),
                Tables\Columns\TextColumn::make('policy_year')
                    ->label('Año'),
                Tables\Columns\TextColumn::make('insuranceCompany.name')
                    ->label('Compañia'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
