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
use Illuminate\Contracts\View\View;
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

    public static function form(Form $form): Form
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
                    ->live()
                    ->required(),
                Forms\Components\DatePicker::make('verification_date')
                    ->label('Fecha de verificación')
                    ->required(),
                Forms\Components\Textarea::make('email_message')
                    ->hidden(fn (Forms\Get $get): bool => $get('status') == IssueStatus::ToReview->value)
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->date('m/d/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('issueType.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Creado'),
                Tables\Columns\TextColumn::make('description'),
                Tables\Columns\TextColumn::make('policy.contact.full_name')
                    ->label('Cliente')
                    ->html()
                    ->url(fn (Issue $record): string => PolicyResource::getUrl('view', ['record' => $record->policy]))
                    ->formatStateUsing(fn (Model $record): string => $record->policy->contact->full_name)
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('policy.contact', function (Builder $query) use ($search): Builder {
                            return $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('second_last_name', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('policy.insuranceCompany.name')
                    ->label('Aseguradora'),
                Tables\Columns\TextColumn::make('policy.policyType.name')
                    ->label('Tipo Poliza'),
                Tables\Columns\TextColumn::make('issueType.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus'),
                Tables\Columns\TextColumn::make('verification_date')
                    ->date('m/d/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                Tables\Actions\BulkAction::make('view_messages')
                    ->label('Ver Mensajes')
                    ->action(function (Collection $records): void {
                        // This will be empty as we're just showing the modal
                    })
                    ->modalContent(function (Collection $records): View {
                        return view('filament.resources.issue.views.view-messages', [
                            'messages' => $records->map(function ($record) {
                                return "Case #: {$record->policy->kynect_case_number}: {$record->email_message}";
                            })->filter()->join("\n\n---\n\n"),
                        ]);
                    }) // Message
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
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
