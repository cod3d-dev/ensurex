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

    protected static ?string $slug = 'commission-statements';

    protected static ?string $navigationGroup = 'Comisiones';

    protected static ?string $modelLabel = 'Reporte de Comisiones';

    protected static ?string $pluralModelLabel = 'Reportes de Comisiones';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Reporte')
                    ->columns(['md' => 3, 'xl' => 3])
                    ->description('Información detallada del reporte de comisiones')
                    ->icon('heroicon-o-document-text')
                    ->collapsible(false)
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('statement_date')
                                    ->label('Fecha del informe')
                                    ->formatStateUsing(fn ($state) => $state ? date('d/m/Y', strtotime($state)) : '-')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium']),
                                Forms\Components\TextInput::make('creator.name')
                                    ->label('Creado por')
                                    ->formatStateUsing(fn ($state, $record) => $record && $record->creator ? $record->creator->name : '-')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium']),
                                Forms\Components\TextInput::make('asistant.name')
                                    ->label('Asistente')
                                    ->formatStateUsing(fn ($state, $record) => $record && $record->asistant ? $record->asistant->name : '-')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium']),
                                Forms\Components\TextInput::make('month_display')
                                    ->label('Mes')
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($record && $record->month) {
                                            $months = [
                                                1 => 'Enero',
                                                2 => 'Febrero',
                                                3 => 'Marzo',
                                                4 => 'Abril',
                                                5 => 'Mayo',
                                                6 => 'Junio',
                                                7 => 'Julio',
                                                8 => 'Agosto',
                                                9 => 'Septiembre',
                                                10 => 'Octubre',
                                                11 => 'Noviembre',
                                                12 => 'Diciembre',
                                            ];

                                            return $months[$record->month] ?? '-';
                                        }

                                        return '-';
                                    })
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium']),

                                Forms\Components\TextInput::make('year')
                                    ->label('Año')
                                    ->formatStateUsing(fn ($state) => $state ?: '-')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium']),

                                Forms\Components\TextInput::make('status')
                                    ->label('Estatus')
                                    ->formatStateUsing(fn ($state, $record) => $record && $record->status ? $record->status->getLabel() : '-')
                                    ->disabled(),

                            ])
                            ->columnSpan(2),
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('total_commission')
                                    ->label('Comisiones')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),
                                Forms\Components\TextInput::make('bonus_amount')
                                    ->label('Bonos')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total')
                                    ->formatStateUsing(fn ($record) => $record ? '$'.number_format((float) $record->total_commission + $record->bonus_amount, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),
                            ])
                            ->columnSpan(1),
                    ]),

                Forms\Components\Section::make('Desglose')
                    ->description('Comisiones por tipo de póliza')
                    ->icon('heroicon-o-chart-pie')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('health_policy_amount')
                                    ->label('Pólizas de Salud')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),

                                Forms\Components\TextInput::make('accident_policy_amount')
                                    ->label('Pólizas de Accidentes')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),

                                Forms\Components\TextInput::make('vision_policy_amount')
                                    ->label('Pólizas de Visión')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),

                                Forms\Components\TextInput::make('dental_policy_amount')
                                    ->label('Pólizas Dentales')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),

                                Forms\Components\TextInput::make('life_policy_amount')
                                    ->label('Pólizas de Vida')
                                    ->formatStateUsing(fn ($state) => $state ? '$'.number_format((float) $state, 2) : '$0.00')
                                    ->disabled()
                                    ->extraAttributes(['class' => 'font-medium text-primary-600']),
                            ])
                            ->columns(3),
                        // Replace the card with individual placeholders for each policy type
                        Forms\Components\Section::make()
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
                                                ->content(function ($record) use ($field) {
                                                    if (! $record || ! $record->exists) {
                                                        return 'N/A';
                                                    }

                                                    $amount = $record->$field ?? 0;
                                                    if ($amount <= 0) {
                                                        return '$0.00 (0%)';
                                                    }

                                                    $total = $record->total_amount - ($record->bonus_amount ?? 0);
                                                    $percent = $total > 0 ? round(($amount / $total) * 100, 1) : 0;

                                                    return '$'.number_format($amount, 2).' ('.$percent.'%)';
                                                });
                                        }

                                        return $components;
                                    })
                                    ->columns(3),
                                Forms\Components\Placeholder::make('total_policies_summary')
                                    ->label('Total Policies Commission')
                                    ->content(function ($record) {
                                        if (! $record || ! $record->exists) {
                                            return 'N/A';
                                        }

                                        $total = $record->total_amount - ($record->bonus_amount ?? 0);

                                        return '$'.number_format($total, 2);
                                    })
                                    ->extraAttributes(['class' => 'text-lg font-bold']),
                            ]),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('statement_date')
                    ->label('Fecha')
                    ->date('m-d-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('asistant.name')
                    ->label('Asistente')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Mes')
                    ->formatStateUsing(function ($state) {
                        $date = \Carbon\Carbon::parse($state);
                        $month = ucfirst($date->translatedFormat('F'));

                        return $month.' '.$date->format('Y');
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Comisión')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_amount')
                    ->label('Bono')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    // ->state(fn (CommissionStatement $record) => number_format((float) $record->total_commission, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'generate' => Pages\CommissionRun::route('/generate'),
            'view' => Pages\ViewCommissionStatement::route('/{record}'),
            'edit' => Pages\EditCommissionStatement::route('/{record}/edit'),
        ];
    }
}
