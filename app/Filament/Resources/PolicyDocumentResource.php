<?php

namespace App\Filament\Resources;

use App\Enums\DocumentStatus;
use App\Filament\Resources\PolicyDocumentResource\Pages;
use App\Models\PolicyDocument;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PolicyDocumentResource extends Resource
{
    protected static ?string $model = PolicyDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $pluralLabel = 'Documentos';

    protected static ?string $singularLabel = 'Documento';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
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

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(Tables\Actions\ViewAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('policy.contact.full_name')
                    ->label('Cliente')
                    ->grow(false)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->whereHas('policy.applicants', function (Builder $query) use ($search): Builder {
                                return $query->where('full_name', 'like', "%{$search}%");
                            });
                    })
                    ->html()
                    ->formatStateUsing(function (string $state, PolicyDocument $record): string {
                        $customers = $state;
                        foreach ($record->policy->additionalApplicants() as $applicant) {
                            $medicaidBadge = '';
                            if ($applicant->pivot->medicaid_client) {
                                $medicaidBadge = '<span class="ml-2 px-2 py-0.5 bg-indigo-900/10 text-indigo-900 rounded-md text-xs font-medium">Medicaid</span>';
                            }

                            $customers .= '<div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1px;">
                                <span style="color: #6b7280; font-size: 0.75rem; max-width: 70%;">'.$applicant->full_name.'</span>
                                '.$medicaidBadge.'
                            </div>';
                        }

                        // Add horizontal line
                        $customers .= '<div style="border-top: 1px solid #e5e7eb; margin-top: 8px; margin-bottom: 6px;"></div>';

                        $enrollmentType = $record->policy->policy_inscription_type?->getLabel() ?? 'N/A';
                        $customers .= '<div style="display: flex; align-items: center;">
                            <span style="font-size: 0.75rem; color: #374151; font-weight: 500;">Tipo de Inscripción:</span>
                            <span style="font-size: 0.75rem; color: #6b7280; margin-left: 4px;">'.$enrollmentType.'</span>
                        </div>';

                        // Add status indicators

                        return $customers;
                    })
                    ->url(fn (PolicyDocument $record) => PolicyResource::getUrl('view', ['record' => $record->policy_id])),
                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Descripción')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->sortable()
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
                Tables\Columns\TextColumn::make('sent_date')
                    ->label('Enviado')
                    ->date('m/d/Y')
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
            ->defaultGroup('policy.code')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPolicyDocuments::route('/'),
            'create' => Pages\CreatePolicyDocument::route('/create'),
            // 'edit' => Pages\EditPolicyDocument::route('/{record}/edit'),
        ];
    }
}
