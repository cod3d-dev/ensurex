<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\PolicyResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;

class ManagePolicyDocument extends ManageRelatedRecords
{
    protected static string $resource = PolicyResource::class;

    protected static string $relationship = 'documents';

    protected static ?string $navigationIcon = 'tni-documents-o';

    protected static ?string $title = 'Documentos';

    public static function getNavigationLabel(): string
    {
        return 'Documentos';
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
                    ->hidden(fn (Forms\Get $get): bool => empty($get('status')) || in_array($get('status'), [DocumentStatus::Pending->value, DocumentStatus::ToAdd->value])),

                // Forms\Components\FileUpload::make('file_name')
                //     ->label('Archivo')
                //     ->columnSpanFull(),
            ]);
    }

    /**
     * Update the policy document_status based on the priority of document statuses
     * and set the next_document_expiration_date field
     *
     * Priority order:
     * 1. rejected - highest priority
     * 2. expired
     * 3. pending
     * 4. sent
     * 5. approved - lowest priority
     */
    private function updatePolicyDocumentStatus(): void
    {
        // Get the parent policy
        $policy = $this->getOwnerRecord();

        // Get all documents for this policy
        $documents = $policy->documents;

        // Default status is ToAdd if no documents, otherwise Approved
        $statusToSet = $documents->isEmpty()
            ? DocumentStatus::ToAdd
            : DocumentStatus::Approved;

        // Reset next expiration date if no documents
        $nextExpirationDate = null;

        // Define priority of statuses (from highest to lowest priority)
        $statusPriority = [
            DocumentStatus::Rejected,
            DocumentStatus::Expired,
            DocumentStatus::Pending,
            DocumentStatus::Sent,
            DocumentStatus::Approved,
        ];

        // Find the highest priority status and its document
        $documentWithHighestPriority = null;

        foreach ($statusPriority as $status) {
            $matchingDocument = $documents->first(function ($document) use ($status) {
                return $document->status === $status;
            });

            if ($matchingDocument) {
                $statusToSet = $status;
                $documentWithHighestPriority = $matchingDocument;
                break;
            }
        }

        // Update the policy's document_status
        $policy->document_status = $statusToSet->value;

        // Set the next_document_expiration_date to the due date of the document with highest priority status
        if ($documentWithHighestPriority && $documentWithHighestPriority->due_date) {
            $policy->next_document_expiration_date = $documentWithHighestPriority->due_date;
        } else {
            // If no priority document found or it has no due date, find the earliest due date of any document
            $documentWithEarliestDueDate = $documents->whereNotNull('due_date')->sortBy('due_date')->first();
            if ($documentWithEarliestDueDate) {
                $policy->next_document_expiration_date = $documentWithEarliestDueDate->due_date;
            } else {
                $policy->next_document_expiration_date = null;
            }
        }

        $policy->save();
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
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Documento')
                    ->after($this->updatePolicyDocumentStatus()),
                // Tables\Actions\AssociateAction::make(),
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
