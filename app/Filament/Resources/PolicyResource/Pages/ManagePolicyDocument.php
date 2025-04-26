<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\PolicyResource;
use App\Models\PolicyDocument;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ManagePolicyDocument extends ManageRelatedRecords
{
    protected static string $resource = PolicyResource::class;

    protected static string $relationship = 'documents';

    protected static ?string $navigationIcon = 'tni-documents-o';



    public static function getNavigationLabel(): string
    {
        return 'Documentos';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        dd($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('document_type_id')
                    ->label('Tipo de Documento')
                    ->relationship('documentType', 'name')
                    ->required(),
//                Forms\Components\Select::make('user_id')
//                    ->label('Subido por')
//                    ->relationship('user', 'name')
//                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->user()->id),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options(DocumentStatus::class)
                    ->live()
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Fecha de vencimiento')
                    ->required(),
                Forms\Components\DatePicker::make('sent_date')
                    ->label('Fecha de envio')
                    ->live()
                    ->hidden(fn (Forms\Get $get): bool => $get('status') == DocumentStatus::Pending->value)
                    ->required(),

                Forms\Components\FileUpload::make('file_name')
                    ->label('Archivo')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->label('Creado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Tipo de Documento')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vence')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->date()
                    ->label('Actualizado')
                    ->sortable(),

            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AssociateAction::make(),
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
