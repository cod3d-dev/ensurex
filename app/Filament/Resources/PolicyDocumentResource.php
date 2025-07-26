<?php

namespace App\Filament\Resources;

use App\Enums\DocumentStatus;
use App\Filament\Resources\PolicyDocumentResource\Pages;
use App\Models\PolicyDocument;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Hidden::make('policy_id'),
                Forms\Components\Select::make('user_id')
                    ->label('Agregado por')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\Select::make('document_type_id')
                    ->relationship('documentType', 'name')
                    ->label('Tipo de Documento')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Estatus')
                    ->options(DocumentStatus::class),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Vence'),
                Forms\Components\TextInput::make('status_updated_by')
                    ->label('Estatus actualizado por')
                    ->disabled()
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            $user = \App\Models\User::find($state);

                            return $user ? $user->name : 'Unknown';
                        }

                        return '';
                    }),
                Forms\Components\TextInput::make('status_updated_at')
                    ->label('Fecha Actualización')
                    ->disabled()
                    ->formatStateUsing(function ($state) {
                        if ($state) {
                            return \Carbon\Carbon::parse($state)->format('d/m/Y');
                        } else {
                            return '';
                        }
                    }),
                Forms\Components\DatePicker::make('sent_date')
                    ->label('Fecha Enviado')
                    ->disabled()
                    ->columnStart(4),
                Forms\Components\TextInput::make('name')
                    ->label('Descripción')
                    ->columnSpanFull()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('Notas')
                    ->columnSpan(3),

            ])->columns([
                'sm' => 4,
                'md => 4',
                'lg => 4',
                'xl => 4',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('policy.contact.full_name')
                    ->label('Cliente')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('policy.contact', function (Builder $query) use ($search): Builder {
                            return $query->where('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('second_last_name', 'like', "%{$search}%");
                        });
                    })
                    ->description(fn (PolicyDocument $record): string => $record->policy->policy_type->getLabel().': '.$record->policy->insuranceCompany?->name ?? '')
                    ->url(fn (PolicyDocument $record): string => PolicyResource::getUrl('view', ['record' => $record->policy])),
                Tables\Columns\TextColumn::make('policy.contact.full_name')
                    ->label('Cliente')
                    ->grow(false)
                    ->searchable()
                    ->html()
                    // ->searchable(query: function (Builder $query, string $search): Builder {
                    //     return $query
                    //         ->whereHas('contact', function (Builder $query) use ($search) {
                    //             $query->where('full_name', 'like', "%{$search}%");
                    //         })
                    //         ->orWhereHas('applicants', function (Builder $query) use ($search) {
                    //             $query->where('full_name', 'like', "%{$search}%");
                    //         });
                    // })
                    // ->tooltip(function (string $state, Policy $record): string {
                    //     $spanishMonths = [
                    //         'January' => 'Enero', 'February' => 'Febrero', 'March' => 'Marzo', 'April' => 'Abril',
                    //         'May' => 'Mayo', 'June' => 'Junio', 'July' => 'Julio', 'August' => 'Agosto',
                    //         'September' => 'Septiembre', 'October' => 'Octubre', 'November' => 'Noviembre', 'December' => 'Diciembre',
                    //     ];
                    //     $month = $record->contact->created_at->format('F');
                    //     $year = $record->contact->created_at->format('Y');
                    //     $spanishDate = $spanishMonths[$month].' de '.$year;
                    //     $customers = 'Cliente desde '.$spanishDate;

                    //     return $customers;
                    // })
                    ->formatStateUsing(function (string $state, PolicyDocument $record): string {
                        $customers = $state;
                        foreach ($record->policy->additionalApplicants() as $applicant) {
                            $medicaidBadge = '';
                            if ($applicant->pivot->medicaid_client) {
                                $medicaidBadge = '<span style="display: inline-block; background-color: #60a5fa; color: white; border-radius: 0.2rem; padding: 0rem 0.2rem; font-size: 0.75rem; font-weight: 500; margin-left: 15px;">Medicaid</span>';
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
                    }),
                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Descripción')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
//                    ->options(DocumentStatus::class)
                    ->action(
                        Tables\Actions\Action::make('changeStatus')
                            ->form([
                                Forms\Components\Select::make('status')
                                    ->options(DocumentStatus::class)
                                    ->required(),
                            ])
                            ->modalWidth('md')
                            ->action(function (PolicyDocument $record, array $data): void {
                                $record->update([
                                    'status' => $data['status'],
                                    'status_updated_by' => auth()->user()->id,
                                    'status_updated_at' => now(),
                                ]);
                                if ($data['status'] == DocumentStatus::Sent->value) {
                                    $record->update([
                                        'sent_date' => now(),
                                    ]);
                                }
                            })
                    )
                    ->label('Estatus')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vence')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_date')
                    ->label('Enviado')
                    ->date()
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
            'edit' => Pages\EditPolicyDocument::route('/{record}/edit'),
        ];
    }
}
