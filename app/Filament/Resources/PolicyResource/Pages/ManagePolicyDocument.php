<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\DocumentStatus;
use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\PolicyResource\Widgets\CustomerInfo;
use App\Models\PolicyDocument;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class ManagePolicyDocument extends ManageRelatedRecords
{
    protected static string $resource = PolicyResource::class;

    protected static string $relationship = 'documents';

    protected static ?string $navigationIcon = 'tni-documents-o';

    protected static ?string $title = 'Documentos';

    public function getHeaderWidgets(): array
    {
        return [
            CustomerInfo::class,
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Documentos';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Hidden::make('policy_id'),
                Forms\Components\Select::make('user_id')
                    ->label('Agregado por')
                    ->relationship('user', 'name')
                    ->disabled(fn () => auth()->user()?->role === \App\Enums\UserRoles::Agent),
                Forms\Components\Select::make('document_type_id')
                    ->relationship('documentType', 'name')
                    ->label('Tipo de Documento')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estatus')
                    ->disabled(fn () => auth()->user()?->role === \App\Enums\UserRoles::Agent)
                    ->options(DocumentStatus::class),
                Forms\Components\DatePicker::make('sent_date')
                    ->label('Fecha Enviado')
                    ->disabled(fn () => auth()->user()?->role === \App\Enums\UserRoles::Agent),
                Forms\Components\TextInput::make('status_updated_by')
                    ->label('Estatus actualizado por')
                    ->disabled(fn () => auth()->user()?->role === \App\Enums\UserRoles::Agent)
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            $user = \App\Models\User::find($state);

                            return $user ? $user->name : 'Unknown';
                        }

                        return '';
                    }),
                Forms\Components\TextInput::make('status_updated_at')
                    ->label('Fecha Actualización')
                    ->disabled(fn () => auth()->user()?->role === \App\Enums\UserRoles::Agent)
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            return \Carbon\Carbon::parse($state)->format('d/m/Y');
                        } else {
                            return '';
                        }
                    }),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Vence')
                    ->disabled(fn () => auth()->user()?->role === \App\Enums\UserRoles::Agent)
                    ->columnStart(4),
                Forms\Components\TextInput::make('name')
                    ->label('Descripción')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->disabled(fn () => auth()->user()?->role === \App\Enums\UserRoles::Agent)
                    ->rows(6)
                    ->columnSpanFull(),
            ])->columns([
                'sm' => 4,
                'md' => 4,
                'lg' => 4,
                'xl' => 4,
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordAction(Tables\Actions\ViewAction::class)
            ->recordTitleAttribute('name')
            ->columns([

                Tables\Columns\TextColumn::make('created_at')
                    ->date('m/d/Y')
                    ->label('Creado')
                    ->sortable(),
                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Tipo de Documento')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->grow()
                    ->label('Nombre')
                    ->html()
                    ->formatStateUsing(function (PolicyDocument $record, string $state): ?string {
                        if (empty($record->notes)) {
                            return '<span>'.e($state).'</span>';
                        }
                        $entries = preg_split('/\n\s*\n/', trim($record->notes));
                        $entries = array_slice($entries, -3);
                        $notes = array_map(function ($entry) {
                            return preg_replace('/^.*\n/', '', $entry, 1);
                        }, $entries);
                        $notesHtml = '<div class="text-xs text-gray-700 ml-2 mt-2">- '.implode('<br>- ', $notes).'</div>';

                        return '<span>'.e($state).'</span>'.$notesHtml;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->badge()
                    ->action(
                        Tables\Actions\Action::make('cambiarEstatus')
                            ->fillForm(fn (PolicyDocument $record) => [
                                'status' => $record->status,
                                'sent_date' => $record->sent_date,
                                'due_date' => $record->due_date,
                            ])
                            ->form([
                                Forms\Components\Select::make('status')
                                    ->label('Estatus')
                                    ->options(
                                        collect(DocumentStatus::cases())
                                            ->reject(fn ($status) => $status->value === DocumentStatus::ToAdd->value)
                                            ->mapWithKeys(fn ($status) => [
                                                $status->value => $status->getLabel(),
                                            ])
                                            ->toArray()
                                    )
                                    ->live()
                                    ->required(),
                                Forms\Components\DatePicker::make('sent_date')
                                    ->label('Fecha de envio')
                                    ->maxDate(Carbon::now())
                                    ->required(fn (Forms\Get $get) => $get('status') === DocumentStatus::Sent)
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Forms\Set $set) {
                                        if (! empty($state)) {
                                            $dueDate = Carbon::parse($state)->addDays(5)->toDateString();
                                            $set('due_date', $dueDate);
                                        } else {
                                            $set('due_date', null);
                                        }
                                    }),
                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Verificación')
                                    ->required(fn (Forms\Get $get) => $get('status') === DocumentStatus::Sent || $get('status') === DocumentStatus::ToVerify),
                                Forms\Components\Textarea::make('note')
                                    ->label('Comentarios')
                                    ->rows(3)
                                    ->required(),

                            ])
                            ->modalWidth('md')
                            ->action(function (PolicyDocument $record, array $data): void {
                                $note = Carbon::now()->toDateTimeString().' - '.auth()->user()->name.":\n".$data['note'];
                                $note = ! empty($record->notes) ? $record->notes."\n\n".$note : $note;

                                $record->update([
                                    'status' => $data['status'],
                                    'notes' => $note,
                                    'sent_date' => $data['sent_date'],
                                    'due_date' => $data['due_date'],
                                    'status_updated_by' => auth()->user()->id,
                                    'status_updated_at' => now(),
                                ]);
                                if ($data['status'] == DocumentStatus::Sent->value) {
                                    $record->update([
                                        'sent_date' => now(),
                                    ]);
                                }
                            })
                    ),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vence')
                    ->date('m/d/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->date('m/d/Y')
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
            DocumentStatus::ToVerify,
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
}
