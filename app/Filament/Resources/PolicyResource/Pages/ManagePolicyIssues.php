<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\IssueStatus;
use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\PolicyResource\Widgets\CustomerInfo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ManagePolicyIssues extends ManageRelatedRecords
{
    protected static string $resource = PolicyResource::class;

    protected static string $relationship = 'issues';

    protected static ?string $navigationIcon = 'gmdi-report-problem-o';

    protected static ?string $title = 'Problemas';

    public static function getNavigationLabel(): string
    {
        return 'Problemas';
    }

    public function getHeaderWidgets(): array
    {
        return [
            CustomerInfo::class,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Select::make('issue_type_id')
                    ->label('Tipo de Problema')
                    ->relationship('issueType', 'name')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options(IssueStatus::class)
                    ->required(),
                Forms\Components\DatePicker::make('verification_date')
                    ->label('Fecha de verificación')
                    ->required(),
                Forms\Components\Textarea::make('email_message')
                    ->label('Mensaje para Kynect')
                    ->columnSpanFull(),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(5)
                            ->label('Notas')
                            ->disabled()
                            ->dehydrated(true),
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Textarea::make('new_note')
                                    ->label('Nueva Nota')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('add_note')
                                        ->label('Agregar Nota')
                                        ->action(function (Forms\Set $set, Forms\Get $get) {
                                            $newNote = $get('new_note');
                                            $set('notes', $get('notes')."\n".auth()->user()->name.': '.now()->toDateTimeString()."\n".$newNote."\n");
                                            $set('new_note', '');
                                        }),
                                ]),
                            ])->columns(1)->columnSpan(1),
                    ])->columnSpanFull()->columns(2),
            ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Caso'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['new_note']);

                        return $data;
                    })
                    ->label('Agregar Problema'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
