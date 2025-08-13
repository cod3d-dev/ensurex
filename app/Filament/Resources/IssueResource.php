<?php

namespace App\Filament\Resources;

use App\Enums\IssueStatus;
use App\Filament\Resources\IssueResource\Pages;
use App\Models\Issue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    // change model name
    protected static ?string $pluralLabel = 'Problemas';

    protected static ?string $singularLabel = 'Problema';

    protected static ?string $navigationIcon = 'iconoir-warning-hexagon';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('created_by')
                    ->default(auth()->user()->id),
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
                    ->live()
                    ->required(),
                Forms\Components\DatePicker::make('verification_date')
                    ->label('Fecha de verificación')
                    ->required(),
                Forms\Components\Textarea::make('email_message')
                    ->label('Mensaje para Kynect')
                    ->readOnly(fn (Forms\Get $get): bool => $get('status') != IssueStatus::ToSend->value)
                    ->required(fn (Forms\Get $get): bool => $get('status') == IssueStatus::ToSend->value)
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->date('m/d/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('issueType.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->date('m/d/Y')
                    ->label('Actualizado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->searchable()
                    ->label('Descripción'),
                Tables\Columns\TextColumn::make('policy.contact.full_name')
                    ->label('Cliente')
                    ->html()
                    ->url(fn (Issue $record): string => PolicyResource::getUrl('view', ['record' => $record->policy]))
                    ->formatStateUsing(fn (Model $record): string => $record->policy->contact->full_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->whereHas('policy.applicants', function (Builder $query) use ($search): Builder {
                                return $query->where('full_name', 'like', "%{$search}%");
                            });
                    })
                    ->formatStateUsing(function (string $state, Issue $record): string {
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
                            <span style="font-size: 0.75rem; color: #6b7280; margin-left: 4px;">'.$record->policy->policy_type->getLabel().' - '.$record->policy->insuranceCompany->name.'</span>
                        </div>';

                        // Add status indicators

                        return $customers;
                    }),
                Tables\Columns\TextColumn::make('issueType.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge(),
                Tables\Columns\TextColumn::make('verification_date')
                    ->label('Verificación')
                    ->date('m/d/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->using(function (Issue $record, array $data): Issue {
                        unset($data['new_note']);
                        $record->update($data);

                        return $record;
                    }),
            ])
            ->headerActions([
                Tables\Actions\BulkAction::make('view_messages')
                    ->label('Ver Mensajes')
                    ->fillForm(function (Collection $records): array {
                        $messages = $records->map(function ($record) {
                            if ($record->policy) {
                                return "Case #: {$record->policy->kynect_case_number}\n{$record->email_message}";
                            }

                            return $record->email_message;
                        })->implode("\n\n---\n\n");

                        return [
                            'kynect_message' => $messages,
                        ];
                    })
                    ->form([
                        Forms\Components\Textarea::make('kynect_message')
                            ->label('Mensaje para Kynect')
                            ->rows(25)
                            ->readOnly()
                            ->columnSpanFull(),
                    ])
                    ->modalHeading('Mensaje para Kynect')
                    ->slideOver()
                    ->stickyModalHeader()
                    ->modalWidth('xl')
                    ->stickyModalFooter()
                    ->modalSubmitActionLabel('Marcar como Enviados')
                    ->modalCancelActionLabel('Cerrar')
                    ->action(function (Collection $records, array $data, array $arguments): void {
                        // Change selected records status to sent
                        $records->each->update([
                            'status' => IssueStatus::Sent,
                            'updated_by' => auth()->user()->id,
                        ]);
                    }),
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
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            //            'edit' => Pages\EditIssue::route('/{record}/edit'),
        ];
    }
}
