<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\UsState;
use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use App\Services\ZipCodeService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\App;

class EditPolicyPayments extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Pago';

    protected static ?string $navigationIcon = 'iconoir-bank';

    public static string|\Filament\Support\Enums\Alignment $formActionsAlignment = 'end';

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label(function () {
                // Check if all pages have been completed
                $record = $this->getRecord();
                $isCompleted = $record->areRequiredPagesCompleted();

                // Return 'Siguiente' if not completed, otherwise 'Guardar'
                return $isCompleted ? 'Guardar' : 'Siguiente';
            })
            ->icon(fn () => $this->getRecord()->areRequiredPagesCompleted() ? '' : 'heroicon-o-arrow-right')
            ->color(function () {
                $record = $this->getRecord();

                return $record->areRequiredPagesCompleted() ? 'primary' : 'success';
            });
    }

    protected function afterSave(): void
    {
        // Get the policy model
        $policy = $this->record;

        // Mark this page as completed
        $policy->markPageCompleted('edit_policy_payments');

        // If all required pages are completed, redirect to the completion page
        if ($policy->areRequiredPagesCompleted()) {
            $this->redirect(PolicyResource::getUrl('edit-complete', ['record' => $policy]));
            return;
        }

        // Get the next uncompleted page and redirect to it
        $incompletePages = $policy->getIncompletePages();
        if (! empty($incompletePages)) {
            $nextPage = reset($incompletePages); // Get the first incomplete page

            // Map page names to their respective routes
            $pageRoutes = [
                'edit_policy' => 'edit',
                'edit_policy_contact' => 'edit-contact',
                'edit_policy_applicants' => 'edit-applicants',
                'edit_policy_applicants_data' => 'edit-applicants-data',
                'edit_policy_income' => 'edit-income',
                'edit_policy_payments' => 'payments',
            ];

            if (isset($pageRoutes[$nextPage])) {
                $this->redirect(PolicyResource::getUrl($pageRoutes[$nextPage], ['record' => $policy]));
            }
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de Pago')
                    ->schema([
                        Forms\Components\Toggle::make('recurring_payment')
                            ->label('Pago Recurrente?')
                            ->default(true)
                            ->required()
                            ->live()
                            ->inline(false)
                            ->columnStart(2),
                        Forms\Components\DatePicker::make('first_payment_date')
                            ->required(fn (Forms\Get $get): bool => $get('recurring_payment'))
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
                                    ->dehydrateStateUsing(fn ($state) => str_replace('-', '', $state))
                                    ->formatStateUsing(function ($state) {
                                        if (! $state) {
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
                            ])->columns(['sm' => 3, 'md' => 3, 'lg' => 3]),
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
                            ])->columns(['sm' => 4, 'md' => 4, 'lg' => 4]),
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
                                Forms\Components\TextInput::make('billing_address_zip')
                                    ->label('Código Postal')
                                    ->disabled(fn (Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (string $state, Forms\Set $set, Forms\Get $get) {
                                        // Skip if copy_home_address is enabled or if zip is empty
                                        if ($get('copy_home_address') || empty($state)) {
                                            return;
                                        }
                                        
                                        // Use ZipCodeService to look up city and state
                                        $zipService = App::make(ZipCodeService::class);
                                        $locationData = $zipService->getLocationFromZipCode($state);
                                        
                                        if ($locationData) {
                                            $set('billing_address_city', $locationData['city']);
                                            $set('billing_address_state', $locationData['state']);
                                        }
                                    }),
                                Forms\Components\TextInput::make('billing_address_city')
                                    ->label('Ciudad')
                                    ->disabled(fn (Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->maxLength(255),
                                Forms\Components\Select::make('billing_address_state')
                                    ->label('Estado')
                                    ->options(UsState::class)
                                    ->disabled(fn (Forms\Get $get) => $get('copy_home_address'))
                                    ->required()
                                    ->dehydrated(true),
                                Forms\Components\TextInput::make('billing_address_1')
                                    ->label('Direccion 1')
                                    ->disabled(fn (Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->columnSpan(2)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('billing_address_2')
                                    ->label('Direccion 2')
                                    ->disabled(fn (Forms\Get $get) => $get('copy_home_address'))
                                    ->dehydrated(true)
                                    ->columnSpan(2)
                                    ->maxLength(255),
                            ])->columns(['sm' => 4, 'md' => 4, 'lg' => 4]),
                    ])
                    ->columns(['sm' => 4, 'md' => 4, 'lg' => 4]),
            ]);

    }
}
