<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPolicyPayments extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Pago';

    protected static ?string $navigationIcon = 'iconoir-bank';
    
    protected function afterSave(): void
    {
        // Mark this page as completed
        $this->record->markPageCompleted('edit_policy_payments');
    }

    public  function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de Pago')
                    ->schema([
                        Forms\Components\Toggle::make('recurring_payment')
                            ->label('Pago Recurrente?')
                            ->default(true)
                            ->required()
                            ->inline(false)
                            ->columnStart(2),
                        Forms\Components\DatePicker::make('first_payment_date')
                            ->label('Fecha del Primer Pago')
                            ->date(),
                        Forms\Components\Select::make('preferred_payment_day')
                            ->label('Fecha de Pago Preferida')
                            ->options(array_combine(range(1, 31), range(1, 31))),

                        Forms\Components\Fieldset::make('Tarjeta')
                            ->schema([
                                Forms\Components\Select::make('payment_card_type')
                                    ->label('Tipo de Tarjeta')
                                    ->options([
                                        'visa' => 'Visa',
                                        'master' => 'Mastercard',
                                        'amex' => 'American Express',
                                        'discover' => 'Discover',
                                        'diners' => 'Diners Club',
                                        'otro' => 'Otro',
                                    ]),
                                Forms\Components\TextInput::make('payment_card_bank')
                                    ->label('Banco')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('payment_card_holder')
                                    ->label('Titular')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('payment_card_number')
                                    ->label('Número')
                                    ->password()
                                    ->revealable()
                                    ->mask('9999-9999-9999-9999')
                                    ->maxLength(255)
                                    ->dehydrateStateUsing(fn($state) => str_replace('-', '', $state))
                                    ->formatStateUsing(function ($state) {
                                        if (!$state) {
                                            return '';
                                        }
                                        $number = str_replace('-', '', $state);
                                        return implode('-', str_split($number, 4));
                                    }),
                                Forms\Components\Select::make('payment_card_exp_month')
                                    ->label('Mes de Vencimiento')
                                    ->options(array_combine(range(1, 12), range(1, 12))),
                                Forms\Components\Select::make('payment_card_exp_year')
                                    ->label('Año de Vencimiento')
                                    ->options(array_combine(range(date('Y'), date('Y') + 10),
                                        range(date('Y'), date('Y') + 10))),
                                Forms\Components\TextInput::make('payment_card_cvv')
                                    ->label('CVV')
                                    ->maxLength(255)
                                    ->mask('999')
                                    ->password()
                                    ->autocomplete(false),
                            ])->columns(3),
                        Forms\Components\Fieldset::make('Cuenta bancaria')
                            ->schema([
                                Forms\Components\TextInput::make('payment_bank_account_bank')
                                    ->label('Banco')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('payment_bank_account_holder')
                                    ->label('Titular')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('payment_bank_account_aba')
                                    ->label('ABA / Routing')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('payment_bank_account_number')
                                    ->label('Cuenta')
                                    ->maxLength(255),
                            ])->columns(4),
                        Forms\Components\Fieldset::make('Dirección de Facturación')
                            ->schema([
                                Forms\Components\Toggle::make('copy_home_address')
                                    ->label('Copiar Direccion Residencial')
                                    ->dehydrated(false)
                                    ->default(false)
                                    ->inline(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, ?Policy $record) {
                                        if ($state) {
                                            $set('billing_address_1', $record->contact->address_line_1);
                                            $set('billing_address_2', $record->contact->address_line_2);
                                            $set('billing_address_city', $record->contact->city);
                                            $set('billing_address_state', $record->contact->state_province);
                                            $set('billing_address_zip', $record->contact->zip_code);
                                        }
                                    }),
                                Forms\Components\TextInput::make('billing_address_1')
                                    ->label('Direccion 1')
                                    ->disabled(fn(Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('billing_address_2')
                                    ->label('Direccion 2')
                                    ->disabled(fn(Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('billing_address_city')
                                    ->label('Ciudad')
                                    ->disabled(fn(Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('billing_address_state')
                                    ->label('Estado')
                                    ->disabled(fn(Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('billing_address_zip')
                                    ->label('Código Postal')
                                    ->disabled(fn(Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->maxLength(255),
                            ])->columns(3),
                    ])
                    ->columns(4)
            ]);

    }
}
