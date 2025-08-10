<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionStatementResource\Pages;
use App\Filament\Resources\CommissionStatementResource\RelationManagers;
use App\Models\CommissionStatement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionStatementResource extends Resource
{
    protected static ?string $model = CommissionStatement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\DatePicker::make('statement_date')
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->relationship('agent', 'name')
                            ->required(),
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date'),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Commission')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('status')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Commission Breakdown by Policy Type')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('health_policy_amount')
                                    ->label('Health Policies')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled(),
                                Forms\Components\TextInput::make('accident_policy_amount')
                                    ->label('Accident Policies')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled(),
                                Forms\Components\TextInput::make('vision_policy_amount')
                                    ->label('Vision Policies')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled(),
                                Forms\Components\TextInput::make('dental_policy_amount')
                                    ->label('Dental Policies')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled(),
                                Forms\Components\TextInput::make('life_policy_amount')
                                    ->label('Life Policies')
                                    ->numeric()
                                    ->prefix('$')
                                    ->disabled(),
                            ])
                            ->columns(3),
                        // Replace the card with individual placeholders for each policy type
                        Forms\Components\Section::make('Commission Distribution')
                            ->schema([
                                Forms\Components\Grid::make()
                                    ->schema(function ($get) {
                                        $components = [];
                                        
                                        $types = ['Health', 'Accident', 'Vision', 'Dental', 'Life'];
                                        $fields = ['health_policy_amount', 'accident_policy_amount', 'vision_policy_amount', 'dental_policy_amount', 'life_policy_amount'];
                                        
                                        foreach ($types as $index => $type) {
                                            $field = $fields[$index];
                                            
                                            $components[] = Forms\Components\Placeholder::make($field.'_percent')
                                                ->label($type)
                                                ->content(function ($record) use ($field, $type) {
                                                    if (!$record || !$record->exists) {
                                                        return 'N/A';
                                                    }
                                                    
                                                    $amount = $record->$field ?? 0;
                                                    if ($amount <= 0) {
                                                        return '$0.00 (0%)';
                                                    }
                                                    
                                                    $total = $record->total_amount - ($record->bonus_amount ?? 0);
                                                    $percent = $total > 0 ? round(($amount / $total) * 100, 1) : 0;
                                                    
                                                    return '$' . number_format($amount, 2) . ' (' . $percent . '%)';
                                                });
                                        }
                                        
                                        return $components;
                                    })
                                    ->columns(3),
                                Forms\Components\Placeholder::make('total_policies_summary')
                                    ->label('Total Policies Commission')
                                    ->content(function ($record) {
                                        if (!$record || !$record->exists) {
                                            return 'N/A';
                                        }
                                        
                                        $total = $record->total_amount - ($record->bonus_amount ?? 0);
                                        return '$' . number_format($total, 2);
                                    })
                                    ->extraAttributes(['class' => 'text-lg font-bold']),
                            ])
                            ->collapsible(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Bonus Information')
                    ->schema([
                        Forms\Components\TextInput::make('bonus_amount')
                            ->label('Bonus Amount')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\Textarea::make('bonus_notes')
                            ->label('Bonus Notes')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Agent')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Pay Period End')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Commission')
                    ->money('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_amount')
                    ->money('$')
                    ->sortable(),
                Tables\Columns\TextColumn::make('health_policy_amount')
                    ->label('Health')
                    ->money('$')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('accident_policy_amount')
                    ->label('Accident')
                    ->money('$')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vision_policy_amount')
                    ->label('Vision')
                    ->money('$')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('dental_policy_amount')
                    ->label('Dental')
                    ->money('$')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('life_policy_amount')
                    ->label('Life')
                    ->money('$')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Generated' => 'success',
                        'Paid' => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('created_at')
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
            RelationManagers\PoliciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionStatements::route('/'),
            'create' => Pages\CreateCommissionStatement::route('/create'),
            'edit' => Pages\EditCommissionStatement::route('/{record}/edit'),
            'commission-run' => Pages\CommissionRun::route('/generate'),
        ];
    }
}
