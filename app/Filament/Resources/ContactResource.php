<?php

namespace App\Filament\Resources;

use App\Enums\Gender;
use App\Enums\MaritialStatus;
use App\Enums\UsState;
use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Contactos';

    protected static ?string $modelLabel = 'Contacto';

    protected static ?string $pluralModelLabel = 'Contactos';

    protected static ?string $recordTitleAttribute = 'first_name'.' '.'last_name';

    protected static ?int $navigationSort = 2;

    protected static int $globalSearchResultsLimit = 5;

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'phone', 'phone2', 'email_address', 'kynect_case_number', 'kommo_id'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->full_name;
    }

    public static function getRelations(): array
    {
        return [
            ContactResource\RelationManagers\OwnedPoliciesRelationManager::class,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Information Tab
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->readonly(),
                        Forms\Components\TextInput::make('created_at')
                            ->label('Cliente desde')
                            ->default(now())
                            ->readOnly()
                            ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('M-Y') : null),
                        Forms\Components\Select::make('created_by')
                            ->relationship('creator', 'name')
                            ->required()
                            ->options(function () {
                                return User::pluck('name', 'id');
                            })
                            ->label('Agregado por'),
                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assigned_to', 'name')
                            ->required()
                            ->options(function () {
                                return User::pluck('name', 'id');
                            })
                            ->label('Asignado a'),
                        Forms\Components\TextInput::make('full_name')
                            ->required()
                            ->autocapitalize('words')
                            ->label('Nombre')
                            ->columnSpan(2),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Fecha Nacimiento'),
                        Forms\Components\Select::make('gender')
                            ->options(Gender::class)
                            ->label('Género'),
                        Forms\Components\Select::make('marital_status')
                            ->options(MaritialStatus::class)
                            ->label('Estado Civil'),
                        Forms\Components\Select::make('preferred_language')
                            ->label('Idioma Preferido')
                            ->options([
                                'spanish' => 'Español',
                                'english' => 'Inglés',
                            ])
                            ->default('spanish'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                            ])
                            ->label('Estatus')
                            ->default('active'),
                        Forms\Components\Select::make('priority')
                            ->options([
                                'high' => 'Alta',
                                'medium' => 'Media',
                                'low' => 'Baja',
                            ])
                            ->label('Prioridad')
                            ->default('medium'),

                    ])->columns(['md' => 4]),

                // Create fieldset for contact info

                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->label('Teléfono')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('phone2')
                            ->tel()
                            ->label('Teléfono 2')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('email_address')
                            ->email()
                            ->label('Correo Electrónico')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('kynect_case_number')
                            ->label('Kynect Case')
                            ->columnSpan(3),
                        Forms\Components\TextInput::make('kommo_id')
                            ->columnSpan(3)
                            ->label('Kommo ID'),
                        Forms\Components\TextInput::make('referral_source')
                            ->label('Fuente de Referencia')
                            ->columnSpan(4),

                    ])->columns(['md' => 10]),

                // Address

                Forms\Components\Section::make('Dirección')
                    ->schema([
                        Forms\Components\TextInput::make('zip_code')
                            ->label('Código Postal'),
                        Forms\Components\TextInput::make('county')
                            ->label('Condado'),
                        Forms\Components\TextInput::make('city')
                            ->label('Ciudad'),
                        Forms\Components\Select::make('state_province')
                            ->options(UsState::class)
                            ->label('Estado'),
                        Forms\Components\TextInput::make('address_line_1')
                            ->label('Línea 1 de la Dirección')
                            ->columnSpan(4),
                        Forms\Components\TextInput::make('address_line_2')
                            ->label('Línea 2 de la Dirección')
                            ->columnSpan(4),
                    ])
                    ->columns(['md' => 4]),

                // Notes
                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(5)
                            ->columnSpan(5),
                        Forms\Components\Textarea::make('new_note')
                            ->label('Nueva Nota')
                            ->live(onBlur: true)
                            ->dehydrated(false)
                            ->rows(3)
                            ->columnSpan(5),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('add_note')
                                ->label('Agregar Nota')
                                ->disabled(function (Forms\Get $get) {
                                    return empty($get('new_note'));
                                })
                                ->action(function (Forms\Set $set, Forms\Get $get) {
                                    $newNote = $get('new_note');
                                    $existingNotes = $get('notes');
                                    $separator = ! empty(trim($existingNotes)) ? "\n\n" : "\n";
                                    $set('notes', $existingNotes.$separator.auth()->user()->name.': '.now()->toDateTimeString()."\n".$newNote."\n");
                                    $set('new_note', '');
                                }),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->label('Código'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre')
                    ->searchable()
                    // ->searchable(['first_name', 'last_name', 'middle_name', 'second_last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('email_address')
                    ->searchable()
                    ->label('Correo Electrónico'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->label('Teléfono')
                    // Add a link to https://ghercys.kommo.com/leads/detail/12788104
                    ->url(fn (Model $record) => 'https://ghercys.kommo.com/leads/detail/'.$record->kommo_id, '_blank'),
                Tables\Columns\TextColumn::make('preferred_language')
                    ->badge()
                    ->label('Idioma Preferido'),
                Tables\Columns\IconColumn::make('is_eligible_for_coverage')
                    ->boolean()
                    ->label('Elegible para Cobertura'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray'
                    })
                    ->label('Estado'),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                    })
                    ->label('Prioridad'),
                Tables\Columns\TextColumn::make('next_follow_up_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Fecha del Próximo Seguimiento'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ])
                    ->label('Estado'),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'high' => 'Alta',
                        'medium' => 'Media',
                        'low' => 'Baja',
                    ])
                    ->label('Prioridad'),
                Tables\Filters\TernaryFilter::make('is_lead')
                    ->label('Estado de Líder'),
                Tables\Filters\TernaryFilter::make('is_eligible_for_coverage')
                    ->label('Elegible para Cobertura'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }

    // public static function getNavigationBadge(): ?string
    // {
    //     return static::getModel()::where('is_lead', true)->count() . ' prospectos';
    // }
}
