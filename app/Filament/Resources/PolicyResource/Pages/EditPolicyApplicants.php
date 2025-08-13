<?php

namespace App\Filament\Resources\PolicyResource\Pages;

use App\Enums\FamilyRelationship;
use App\Filament\Resources\PolicyResource;
use App\Models\Contact;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPolicyApplicants extends EditRecord
{
    protected static string $resource = PolicyResource::class;

    protected static ?string $navigationLabel = 'Miembros';

    protected static ?string $navigationIcon = 'carbon-pedestrian-family';

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }

    protected function afterSave(): void
    {
        // Get the policy model
        $policy = $this->record;

        // Count the total family members (all applicants in the pivot table)
        $totalFamilyMembers = $policy->policyApplicants()->count();

        // Count applicants where is_covered_by_policy is true
        $totalCoveredApplicants = $policy->policyApplicants()
            ->where('is_covered_by_policy', true)
            ->count();

        // Count applicants where medicaid_client is true
        $totalMedicaidApplicants = $policy->policyApplicants()
            ->where('medicaid_client', true)
            ->count();

        // Update the policy with the new counts
        $policy->update([
            'total_family_members' => $totalFamilyMembers,
            'total_applicants' => $totalCoveredApplicants,
            'total_applicants_with_medicaid' => $totalMedicaidApplicants,
        ]);

        // Mark this page as completed
        $policy->markPageCompleted('edit_policy_applicants');

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
                Forms\Components\Section::make('Miembros')
                    ->schema([
                        Forms\Components\Repeater::make('policyApplicants')
                            ->itemLabel(function (array $state): ?string {
                                if (isset($state['contact_id'])) {
                                    $contact = Contact::find($state['contact_id']);

                                    return $contact->full_name;
                                }

                                return null;
                            })
                            ->deleteAction(
                                fn (Action $action) => $action->requiresConfirmation(),
                            )
                            ->extraItemActions([
                                Action::make('deleteApplicant')
                                    ->icon('heroicon-m-trash')
                                    ->action(function (array $arguments, Repeater $component): void {
                                        $selectedApplicant = $component->getItemState($arguments['item']);

                                        if ($selectedApplicant['relationship_with_policy_owner'] === 'self') {
                                            Notification::make()
                                                ->title('No se puede eliminar')
                                                ->body('No se puede eliminar al titular de la póliza.')
                                                ->danger()
                                                ->send();
                                        } else {
                                            // Get the current state of the repeater
                                            $state = $component->getState();

                                            // Remove the item with the specified key
                                            unset($state[$arguments['item']]);

                                            // Update the repeater state
                                            $component->state($state);

                                            Notification::make()
                                                ->title('Aplicante eliminado')
                                                ->body('El aplicante ha sido eliminado de la póliza. Los cambios no serán efectivos hasta que se guarde el formulario.')
                                                ->success()
                                                ->send();
                                        }
                                    }),
                                // ->modalHeading('¿Eliminar aplicante?')
                                // ->modalDescription('¿Está seguro que desea eliminar este aplicante?')
                                // ->modalSubmitActionLabel('Sí, eliminar')
                                // ->modalCancelActionLabel('No, cancelar'),
                            ])
                            ->label('Aplicantes Adicionales')
                            ->relationship()
                            ->columnSpanFull()
                            ->deletable(false)
                            ->hiddenLabel(true)
                            ->orderColumn('sort_order')
                            ->schema([
                                Forms\Components\Fieldset::make('Datos')
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\Select::make('contact_id')
                                                    ->relationship('contact', 'full_name')
                                                    ->columnSpan(4)
                                                    ->fixIndistinctState()
                                                    ->label('Nombre')
                                                    ->required()
                                                    ->live()
                                                    ->getOptionLabelFromRecordUsing(function (Contact $record) {
                                                        $label = $record->full_name;
                                                        if ($record->state_province) {
                                                            $label .= ' / '.$record->state_province->getLabel();
                                                        }

                                                        if ($record->age) {
                                                            $label .= ' / '.$record->age;
                                                        }

                                                        return $label;
                                                    })
                                                    ->createOptionForm([
                                                        Forms\Components\TextInput::make('full_name')
                                                            ->label('Nombre')
                                                            ->required(),
                                                    ])
                                                    ->createOptionUsing(function (array $data, Get $get) {
                                                        // If this is the 'self' relationship, prevent creation
                                                        if ($get('relationship_with_policy_owner') === 'self') {
                                                            // Show an error notification
                                                            Notification::make()
                                                                ->title('No se puede crear un nuevo contacto')
                                                                ->body('No se puede crear un nuevo contacto para el titular de la póliza. Por favor, seleccione un contacto existente.')
                                                                ->danger()
                                                                ->persistent()
                                                                ->send();

                                                            // Redirect to reload the page
                                                            $this->redirect(PolicyResource::getUrl('edit-applicants', ['record' => $this->record]));

                                                            return null;
                                                        }

                                                        // For other relationships, create the contact normally
                                                        return Contact::create($data)->getKey();
                                                    })
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        if ($state) {
                                                            $contact = Contact::find($state);
                                                            if ($contact) {
                                                                $set('contact_code', $contact->code);
                                                            }
                                                        }
                                                    })
                                                    ->live()
                                                    ->searchable(),
                                                Forms\Components\TextInput::make('contact_code')
                                                    ->label('Código')
                                                    ->formatStateUsing(function ($state, $record = null) {
                                                        return $record && $record->contact ? $record->contact->code : null;
                                                    })
                                                    ->dehydrated(false)
                                                    ->disabled(),
                                                Forms\Components\Toggle::make('is_covered_by_policy')
                                                    ->inline(false)
                                                    ->default(true)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        if ($state === true) {
                                                            $set('medicaid_client', false);
                                                        }
                                                    })
                                                    ->label('¿Aplicante?')
                                                    ->columnStart(6),
                                                Forms\Components\Toggle::make('medicaid_client')
                                                    ->inline(false)
                                                    ->default(false)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, $set) {
                                                        if ($state === true) {
                                                            $set('is_covered_by_policy', false);
                                                        }
                                                    })
                                                    ->label('¿Medicaid?'),
                                                Forms\Components\Select::make('relationship_with_policy_owner')
                                                    ->columnSpan(4)
                                                    ->label('¿Relación con el cliente principal?')
                                                    ->options(FamilyRelationship::class)
                                                    ->disableOptionWhen(fn ($state, $value): bool => ($state === null && $value === 'self') ||
                                                        ($state !== null && $value === 'self') ||
                                                        $state === 'self'
                                                    )
                                                    ->required(),
                                                Forms\Components\TextInput::make('kynect_case_number')
                                                    ->label('Caso Kynect')
                                                    ->columnStart(6)
                                                    ->columnSpan(2)
                                                    ->disabled()
                                                    ->dehydrated(false),
                                            ])
                                            ->columns(7)
                                            ->columnSpanFull(),
                                    ])->columns(4),
                            ])
                            ->reorderable(false)
                            ->addActionLabel('Agregar Aplicante')
                            ->collapsible(true)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(3),
                    ]),

            ])->columns(6);
    }
}
