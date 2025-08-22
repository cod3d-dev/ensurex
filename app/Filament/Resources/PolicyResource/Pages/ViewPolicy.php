<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Enums\PolicyStatus;
use App\Filament\Resources\PolicyResource;
use App\Models\Policy;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPolicy extends ViewRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Poliza';

    protected static ?string $navigationIcon = 'carbon-pedestrian-family';

    public $readonly = true;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('change_status')
                ->label('Cambiar Estatus')
                ->color('info')
                ->icon('mdi-tag-edit-outline')
                ->form([
                    Forms\Components\Split::make([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Estatus')
                                    ->options(PolicyStatus::class)
                                    ->required()
                                    ->preload()
                                    ->disableOptionWhen(fn (string $value): bool => ($value === PolicyStatus::ToVerify->value)
                                    )
                                    ->searchable(),
                                Forms\Components\Textarea::make('notas')
                                    ->label('Notas')
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('client_notified')
                                    ->label('Cliente Informado')
                                    ->default(fn (Policy $record) => $record->client_notified),
                                Forms\Components\Toggle::make('initial_paid')
                                    ->label('Inicial Pagada')
                                    ->default(fn (Policy $record) => $record->initial_paid),
                                Forms\Components\Toggle::make('autopay')
                                    ->label('Cotizacion')
                                    ->default(fn (Policy $record) => $record->autopay),
                                Forms\Components\Toggle::make('aca')
                                    ->label('ACA')
                                    ->visible(fn (Policy $record) => $record->requires_aca)
                                    ->default(fn (Policy $record) => $record->aca),
                            ]),
                    ]),
                ])
                ->action(function (Policy $record, array $data): void {
                    $record->status = $data['status'];
                    $note = Carbon::now()->toDateTimeString().' - '.auth()->user()->name.":\nCambio de Estatus: ".PolicyStatus::from($data['status'])->getLabel()."\n".$data['notas']."\n\n";
                    $record->notes = ! empty($record->notes) ? $record->notes."\n\n".$note : $note;
                    $record->client_notified = $data['client_notified'];
                    $record->initial_paid = $data['initial_paid'];
                    $record->autopay = $data['autopay'];
                    if ($record->requires_aca) {
                        $record->aca = $data['aca'];
                    }
                    $record->save();

                    Notification::make()
                        ->title('Estatus Cambiado')
                        ->success()
                        ->send();
                }),
            Action::make('edit')
                ->label('Editar')
                ->color('warning')
                ->url(PolicyResource::getUrl('edit', ['record' => $this->record])),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Póliza')
                    ->columns(['sm' => 2, 'md' => 4, 'xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label('Número de Póliza'),
                        Infolists\Components\TextEntry::make('policy_year')
                            ->label('Año Efectivo'),
                        Infolists\Components\TextEntry::make('policy_type')
                            ->label('Tipo de Póliza'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Estatus'),
                        Infolists\Components\TextEntry::make('agent.name')
                            ->label('Cuenta'),
                        Infolists\Components\TextEntry::make('insuranceCompany.name')
                            ->label('Aseguradora'),
                        Infolists\Components\TextEntry::make('policy_plan')
                            ->label('Plan'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Asistente'),
                        Infolists\Components\TextEntry::make('policy_inscription_type')
                            ->label('Tipo de Inscripción'),
                        Infolists\Components\TextEntry::make('activation_date')
                            ->label('Fecha de Activación'),
                        Infolists\Components\TextEntry::make('effective_date')
                            ->label('Fecha de Efectividad')
                            ->date('m-d-Y'),
                        Infolists\Components\TextEntry::make('contact.full_name')
                            ->label('Contacto'),
                        Infolists\Components\Section::make('Grupo Familiar')
                            ->columns(['sm' => 2, 'md' => 4, 'xl' => 4])
                            ->schema([
                                Infolists\Components\TextEntry::make('total_family_members')
                                    ->label('Total Miembros Familiares'),
                                Infolists\Components\TextEntry::make('total_applicants')
                                    ->label('Total Aplicantes'),
                                Infolists\Components\TextEntry::make('total_applicants_with_medicaid')
                                    ->label('Total Miembros Medicaid'),
                                Infolists\Components\RepeatableEntry::make('applicants')
                                    ->label('Aplicantes')
                                    ->columnStart(1)
                                    ->columnSpanFull()
                                    ->columns(['sm' => 4, 'md' => 6, 'xl' => 6])
                                    ->schema([
                                        Infolists\Components\TextEntry::make('full_name')
                                            ->label('Nombre')
                                            ->html()
                                            ->formatStateUsing(function ($state, $record) {
                                                $html = $state;
                                                $html .= '<hr>'.FamilyRelationship::from($record->pivot->relationship_with_policy_owner)->getLabel();

                                                return $html;
                                            }),
                                        Infolists\Components\IconEntry::make('pivot.is_covered_by_policy')
                                            ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                            ->color(fn ($state) => $state ? 'success' : 'danger')
                                            ->label('Aplicante'),
                                        Infolists\Components\TextEntry::make('date_of_birth')
                                            ->label('Fecha de Nacimiento')
                                            ->date('m-d-Y'),
                                        Infolists\Components\TextEntry::make('gender')
                                            ->label('Género'),
                                        Infolists\Components\TextEntry::make('pivot.contact_id')
                                            ->label('Ingresos')
                                            ->formatStateUsing(function ($record): string {
                                                $yearlyIncome = $record->pivot->yearly_income ?? 0;
                                                $selfEmployedIncome = $record->pivot->self_employed_yearly_income ?? 0;
                                                $totalIncome = $yearlyIncome + $selfEmployedIncome;

                                                if ($totalIncome <= 0) {
                                                    return '';
                                                }

                                                return '$'.number_format($totalIncome, 2, '.', ',');
                                            }),
                                        Infolists\Components\View::make('ssn')
                                            ->label('Inmigración')
                                            ->view('filament.infolists.inmigration-document'),

                                    ]),
                            ]),
                        Infolists\Components\Section::make('Dirección Residencia')
                            ->schema([
                                Infolists\Components\TextEntry::make('contact.zip_code')
                                    ->label('Código Postal'),
                                Infolists\Components\TextEntry::make('contact.city')
                                    ->label('Ciudad'),
                                Infolists\Components\TextEntry::make('contact.state_province')
                                    ->label('Estado'),
                                Infolists\Components\TextEntry::make('contact.address_line_1')
                                    ->label('Dirección'),

                            ])->columns(['sm' => 2, 'md' => 4, 'xl' => 4]),
                        Infolists\Components\Section::make('Dirección Facturación')
                            ->schema([
                                Infolists\Components\TextEntry::make('billing_address_zip')
                                    ->label('Código Postal'),
                                Infolists\Components\TextEntry::make('billing_address_city')
                                    ->label('Ciudad'),
                                Infolists\Components\TextEntry::make('billing_address_state')
                                    ->label('Estado'),
                                Infolists\Components\TextEntry::make('billing_address_1')
                                    ->label('Dirección'),

                            ])->columns(['sm' => 2, 'md' => 4, 'xl' => 4]),
                        Infolists\Components\Section::make('Pago')
                            ->schema([
                                Infolists\Components\TextEntry::make('recurring_payment')
                                    ->label('Pago Recurrente'),
                                Infolists\Components\TextEntry::make('first_payment_date')
                                    ->label('Fecha del Primer Pago')
                                    ->date('m-d-Y'),
                                Infolists\Components\TextEntry::make('preferred_payment_day')
                                    ->label('Fecha de Pago Preferida'),
                                Infolists\Components\Section::make('Tarjeta')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('payment_card_type')
                                            ->label('Tipo de Tarjeta')
                                            ->extraAttributes([
                                                'class' => 'capitalize',
                                            ]),
                                        Infolists\Components\TextEntry::make('payment_card_holder')
                                            ->label('Titular'),
                                        Infolists\Components\TextEntry::make('payment_card_number')
                                            ->label('Número'),
                                        Infolists\Components\TextEntry::make('payment_card_exp_month')
                                            ->label('Vence')
                                            ->formatStateUsing(fn ($record) => $record->payment_card_exp_month.'/'.$record->payment_card_exp_year),
                                        Infolists\Components\TextEntry::make('payment_card_cvv')
                                            ->label('CVV'),
                                    ])->columns(['sm' => 5]),
                                Infolists\Components\Section::make('Cuenta Bancaria')
                                    ->schema([
                                        Infolists\Components\TextEntry::make('payment_bank_account_bank')
                                            ->label('Banco')
                                            ->extraAttributes([
                                                'class' => 'capitalize',
                                            ]),
                                        Infolists\Components\TextEntry::make('payment_bank_account_holder')
                                            ->label('Titular'),
                                        Infolists\Components\TextEntry::make('payment_bank_account_number')
                                            ->label('Número'),
                                        Infolists\Components\TextEntry::make('payment_bank_account_aba')
                                            ->label('ABA / Routing'),
                                    ])->columns(['sm' => 4]),

                            ])->columns(['sm' => 3]),
                    ]),
            ]);
    }
}
