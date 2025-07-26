@php use App\Enums\FamilyRelationship; @endphp
<x-filament-panels::page>

    <x-filament::breadcrumbs :breadcrumbs="[
    '/quotes' => 'Cotizaciones',
    '/quotes/' . $record->id => 'Cotización',
]" />

    <div class="grid grid-cols-4 gap-4">
        <div class="col-span-4">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        Cotización
                        <x-filament::button
                            :href="App\Filament\Resources\QuoteResource::getUrl('edit', ['record' => $record])" tag="a"
                            color="warning" outlined>
                            Editar
                        </x-filament::button>
                    </div>
                </x-slot>

                {{-- Contact Information --}}
                {{-- Create a table with the contact information of the client --}}
                <x-filament::section class="bg-gray-100">
                    <div class="grid grid-cols-5 gap-4 print:grid-cols-5 !grid-cols-5"
                        style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr));">
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Cuenta</p>
                            <p class="mt-1">{{ $record->agent->name ?? 'No asignada' }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Asistente</p>
                            <p class="mt-1">{{ $record->user->name }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Fecha Creación</p>
                            <p class="mt-1">{{ $record->created_at ? $record->created_at->format('m/d/Y') : 'N/A' }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Tipo</p>
                            <p class="mt-1">
                                @if(is_array($record->policy_types) && count($record->policy_types) > 0)
                                @foreach($record->policy_types as $policy_type)
                                {{ is_string($policy_type) ? (\App\Enums\PolicyType::tryFrom($policy_type)?->getLabel() ?? 'Unknown') : 'Unknown' }}@if(!$loop->last), @endif
                                @endforeach
                                @elseif($record->policy_types && is_string($record->policy_types))
                                {{ \App\Enums\PolicyType::tryFrom($record->policy_types)?->getLabel() ?? 'Unknown' }}
                                @else
                                N/A
                                @endif
                            </p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium">Estado</p>
                            <x-small-badge :bgColor="$record->status->getColor()">
                                {{ $record->status->getLabel() }}
                            </x-small-badge>

                            @if($record->status->value == 'converted')

                            <x-filament::button
                                :href="App\Filament\Resources\PolicyResource::getUrl('view', ['record' => $record->policy_id])"
                                tag="a" color="success" icon="heroicon-o-link" size="sm">
                                Ver
                            </x-filament::button>
                            @endif

                        </div>


                    </div>
                </x-filament::section>


                <x-filament::section class="bg-gray-100 !mt-10" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Contacto
                    </x-slot>
                    <div class="grid grid-cols-4 gap-4 print:grid-cols-4 !grid-cols-4"
                        style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr));">
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Nombre</p>
                            <p class="mt-1">{{ $record->contact->full_name }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Teléfono</p>
                            <p class="mt-1">{{ preg_replace('/^(\d{3})(\d{3})(\d{4})$/', '($1) $2-$3',
                                $record->contact->phone) }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Teléfono 2</p>
                            <p class="mt-1">{{ $record->contact->phone2 ? preg_replace('/^(\d{3})(\d{3})(\d{4})$/',
                                '($1) $2-$3', $record->contact->phone2) : '' }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Correo Electrónico</p>
                            <p class="mt-1">{{ $record->contact->email_address }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Código Postal</p>
                            <p class="mt-1">{{ $record->contact->zip_code }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Ciudad</p>
                            <p class="mt-1">{{ $record->contact->city }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Condado</p>
                            <p class="mt-1">{{ $record->contact->county }}</p>
                        </div>
                        <div class="col-span-1">
                            <p class="text-sm font-medium text-gray-500">Estado</p>
                            <p class="mt-1">{{ $record->contact->state_province->getLabel() }}</p>
                        </div>
                    </div>
                </x-filament::section>



                {{-- Applicants --}}
                <x-filament::section class="bg-gray-100 mt-8" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Aplicantes
                    </x-slot>

                    @php
                    // Get all applicants from the applicants collection
                    $applicants = collect($record->applicants ?? []);
                    @endphp

                    @if($applicants->isNotEmpty())
                    <div class="w-full">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aplicante</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Edad</th>
                                    <th
                                        class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Género</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fuma</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Embarazada</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Medicaid</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($applicants as $applicant)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{
                                        \App\Enums\FamilyRelationship::from($applicant['relationship'])->getLabel() ??
                                        'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $applicant['age'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ($applicant['gender'] ?? '') ===
                                        'male' ? 'Masculino' : 'Femenino' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ($applicant['is_tobacco_user'] ??
                                        false) ? 'Sí' : 'No' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ ($applicant['is_pregnant'] ?? false)
                                        ? 'Sí' : 'No' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{
                                        ($applicant['is_eligible_for_coverage'] ?? false) ? 'Sí' : 'No' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </x-filament::section>

                {{-- Income --}}
                <x-filament::section class="bg-gray-100 mt-10" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Ingresos
                    </x-slot>

                    @php
                    // Filter applicants to only show those with income
                    $applicantsIncome = $applicants->filter(function ($applicant) {
                    return (($applicant['yearly_income'] ?? 0) > 0) || (($applicant['self_employed_yearly_income'] ?? 0)
                    > 0);
                    });
                    @endphp

                    @if($applicantsIncome->isNotEmpty())
                    <div class="w-full">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aplicante</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Hora $</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        H x S</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        HE $</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        HE x S</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        S x A</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ingresos</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($applicantsIncome as $applicant)
                                <tr>
                                    @if(!($applicant['is_self_employed'] ?? false))
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ \App\Enums\FamilyRelationship::from($applicant['relationship'])->getLabel() }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $applicant['income_per_hour'] ??
                                        'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $applicant['hours_per_week'] ?? 'N/A'
                                        }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $applicant['income_per_extra_hour']
                                        ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $applicant['extra_hours_per_week'] ??
                                        'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $applicant['weeks_per_year'] ?? 'N/A'
                                        }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{
                                        number_format($applicant['yearly_income'] ?? 0, 2) }}</td>
                                    @else
                                    @php
                                    $relation = isset($applicant['relationship']) ?
                                    FamilyRelationship::from($applicant['relationship'])->getLabel() : 'N/A';
                                    @endphp

                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $relation }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right pe-10" colSpan="4">
                                        Self-Employed</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right pe-10">{{
                                        $applicant['self_employed_profession'] ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{
                                        number_format($applicant['self_employed_yearly_income'] ?? 0, 2) }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-100 divide-y divide-gray-200 p-10">
                                <tr>
                                    <td class="px-6 py-4  font-medium text-gray-900 text-right" colSpan="6">Total
                                        Ingresos</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-left pe-10">$ {{
                                        number_format($record->estimated_household_income) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @endif

                </x-filament::section>


                {{-- Health --}}
                @if($record->prescription_drugs || $record->preferred_doctor)
                <x-filament::section class="bg-gray-100 mt-10" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Salud
                    </x-slot>

                    @if($record->preferred_doctor)
                    Doctor Preferido: {{ $record->preferred_doctor }}
                    @endif

                    @if($record->prescription_drugs)
                    <div class="w-full">
                        <table class="w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th
                                        class="w-2/4 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Aplicante</th>
                                    <th
                                        class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">
                                        Medicamento</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">
                                        Dosis</th>
                                    <th
                                        class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-center">
                                        Meses</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($record->prescription_drugs as $drug)
                                @php
                                // dd($record->additional_applicants);
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $drug['applicant'] ===
                                        'main' ? 'Principal' : $drug['applicant'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-center">{{ $drug['name'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-center">{{ $drug['dosage'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-center">{{ $drug['frequency'] }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                </x-filament::section>

                @endIf

                <div class="text-right justify-between mt-8" style="margin-top: 2.5rem !important;">
                    <x-filament::button
                        href="https://www.healthsherpa.com/shopping?_agent_id=nil&carrier_id=nil&source=agent-home"
                        tag="a" target="_blank" class="ml-3" color="info" outlined>
                        Health Sherpa
                    @if($record->contact->kommo_id)
                    <x-filament::button
                        href="https://ghercys.kommo.com/leads/detail/{{$record->contact->kommo_id ?? '' }}"
                        tag="a" target="_blank" class="ml-3" color="info" outlined>
                        Kommo
                    </x-filament::button>
                    @endif
                    
                    </x-filament::button>
                    <div class="ml-3 inline-flex w-5">
                        
                    </div>

                    @if($record->status->value == 'pending' || $record->status->value == 'accepted')
                    <x-filament::button wire:click="changeQuoteStatusToSent" class="ml-3" color="warning" outlined>
                        Marcar como Enviada
                    </x-filament::button>
                    @endif
                    

                    @if($record->status->value != 'converted')
                    <x-filament::button x-data="{}"
                        x-on:click="$dispatch('open-modal', { id: 'convert-to-policy-confirmation' })" class="ml-3"
                        color="success"
                        outlined>
                        Crear Polizas
                    </x-filament::button>

                    @endif

                </div>

                <x-filament::modal id="convert-to-policy-confirmation" icon="heroicon-o-exclamation-triangle"
                        icon-color="success" :heading="'Crear Poliza'"
                        :description="'Se creara una poliza a partir de esta cotización.'" width="md">
                        <div class="flex justify-end gap-x-2 mt-5">
                            <x-filament::button
                                x-on:click="$dispatch('close-modal', { id: 'convert-to-policy-confirmation' })">
                                Cancelar
                            </x-filament::button>

                            <x-filament::button wire:click="convertToPolicy" color="success">
                                Crear y Editar
                            </x-filament::button>
                        </div>
                    </x-filament::modal>


            </x-filament::section>







        </div>
    </div>
</x-filament-panels::page>