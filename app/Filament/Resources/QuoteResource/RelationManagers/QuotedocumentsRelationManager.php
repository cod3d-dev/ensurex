<?php

namespace App\Filament\Resources\QuoteResource\RelationManagers;

use App\Models\QuoteDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuoteDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'quoteDocuments';
    protected static ?string $title = 'Documentos';
    // protected static ?string $recordTitleAttribute = 'original_name';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('quote_id'),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\FileUpload::make('file_path')
                    ->required()
                    ->directory('policy_documents')
                    ->acceptedFileTypes(['application/pdf', 'image/*', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                    ->maxSize(10240)
                    ->label('Archivo'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('m/d/Y H:i'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('')
//                    ->square()
                    ->extraImgAttributes(fn (QuoteDocument $record): array => [
                        'hidden' => ! in_array(strtolower(pathinfo($record->file_path, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'svg']),
                    ]),
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
