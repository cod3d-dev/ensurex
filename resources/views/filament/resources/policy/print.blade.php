@php use App\Enums\FamilyRelationship; @endphp
<x-filament-panels::page>

    <x-filament::breadcrumbs :breadcrumbs="[
        '/admin/policies' => 'Polizas',
        '/admin/policies/' . $record->id => $record->id,
        ]" />

    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
        <div style="grid-column: span 4 / span 4;">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        Poliza
                        <x-filament::button
                            :href="App\Filament\Resources\PolicyResource::getUrl('edit', ['record' => $record])"
                            tag="a"
                            color="warning"
                        >
                            Editar
                        </x-filament::button>
                    </div>
                </x-slot>

                {{-- Contact Information --}}
                {{-- Create a table with the contact information of the client --}}
                <x-filament::section class="bg-gray-100">
                    <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1rem;">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Cuenta</p>
                            <p class="mt-1">{{ $record->Agent->name ?? 'No asignada' }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Asistente</p>
                            <p class="mt-1">{{ $record->user->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Fecha Creación</p>
                            <p class="mt-1">{{ $record->created_at->format('m/d/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tipo</p>
                            <p class="mt-1">{{ $record->policy_type->getLabel() }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium">Estado</p>
                            <x-small-badge :bgColor="$record->status?->getColor()">
                                {{ $record->status?->getLabel() }}
                            </x-small-badge>
                        </div>


                        <div style="grid-column-start: 3;">
                            <p class="text-sm font-medium text-gray-500">Año</p>
                            <p class="mt-1">{{ $record->policy_year }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">Fecha Inicio</p>
                            <p class="mt-1">
                                @if($record->effective_date)
                                    @if(is_string($record->effective_date))
                                        {{ \Carbon\Carbon::parse($record->effective_date)->format('d/m/Y') }}
                                    @else
                                        {{ $record->effective_date->format('d/m/Y') }}
                                    @endif
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-sm font-medium text-gray-500">Fecha Terminación</p>
                            <p class="mt-1">
                                @if($record->end_date)
                                    @if(is_string($record->end_date))
                                        {{ \Carbon\Carbon::parse($record->end_date)->format('d/m/Y') }}
                                    @else
                                        {{ $record->end_date->format('d/m/Y') }}
                                    @endif
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>

                        <div style="grid-column: span 1 / span 1;">
                            <p class="text-sm font-medium text-gray-500">Aseguradora</p>
                            <p class="mt-1">{{ $record->insuranceCompany?->name }}</p>
                        </div>

                        <div style="grid-column: span 1 / span 1;">
                            <p class="text-sm font-medium text-gray-500">Plan</p>
                            <p class="mt-1">{{ $record->policy_plan }}</p>
                        </div>

                        <div style="grid-column: span 1 / span 1;">
                            <p class="text-sm font-medium text-gray-500">Prima</p>
                            <p class="mt-1">$ {{ $record->premium_amount }}</p>
                        </div>
                    </div>
                </x-filament::section>


                <x-filament::section class="bg-gray-100 !mt-10" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Aplicante Principal
                    </x-slot>

                    {{-- Personal Information Section --}}
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información Personal</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Nombre</p>
                                <p class="mt-1">{{ $record->contact->full_name }}</p>
                            </div>
                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Género</p>
                                <p class="mt-1">{{ $record->contact->gender?->getLabel() }}</p>
                            </div>
                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Fecha de Nacimiento</p>
                                <p class="mt-1">{{ $record->contact->date_of_birth ? \Carbon\Carbon::parse($record->contact->date_of_birth)->format('m-d-Y') : '' }}</p>
                            </div>
                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Estado Civil</p>
                                <p class="mt-1">{{ $record?->contact?->marital_status?->getLabel() }}</p>
                            </div>
                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Teléfono</p>
                                <p class="mt-1">{{ $record->contact->phone }}</p>
                            </div>
                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Correo Electrónico</p>
                                <p class="mt-1">{{ $record->contact->email }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Address Section --}}
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Dirección</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Dirección Completa</p>
                                <p class="mt-1">{{ $record->contact->full_address_lines }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Código Postal</p>
                                <p class="mt-1">{{ $record->contact->zip_code }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Condado</p>
                                <p class="mt-1">{{ $record->contact->county }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Ciudad</p>
                                <p class="mt-1">{{ $record->contact->city }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Estado</p>
                                <p class="mt-1">{{ $record->contact->state_province->getLabel() }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Immigration Status Section --}}
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #4B7280;">Información Migratoria</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Estado Migratorio</p>
                                <p class="mt-1">
                                    @php
                                        $status = App\Enums\ImmigrationStatus::tryFrom($record->contact_information['immigration_status'] ?? '');
                                    @endphp
                                    {{ $status?->getLabel() }}
                                    @if($status === App\Enums\ImmigrationStatus::Other && isset($record->contact_information['immigration_status_category']))
                                        - {{ $record->contact_information['immigration_status_category'] }}
                                    @endif
                                </p>
                            </div>

                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Identificación</p>
                                <p class="mt-1">
                                    @if(isset($record->contact_information['ssn']) && !empty($record->contact_information['ssn']))
                                        SSN: {{ $record->contact_information['ssn'] }}
                                    @elseif(isset($record->contact_information['passport']) && !empty($record->contact_information['passport']))
                                        Pasaporte: {{ $record->contact_information['passport'] }}
                                    @elseif(isset($record->contact_information['green_card_number']) && !empty($record->contact_information['green_card_number']))
                                        Green Card: {{ $record->contact_information['green_card_number'] }}
                                    @elseif(isset($record->contact_information['alien_number']) && !empty($record->contact_information['alien_number']))
                                        Alien #: {{ $record->contact_information['alien_number'] }}
                                    @endif
                                </p>
                            </div>

                            <div style="grid-column: span 1 / span 1;">
                                <p class="text-sm font-medium text-gray-500">Permiso de Trabajo</p>
                                <p class="mt-1">
                                    @if(isset($record->contact_information['work_permit_number']) && !empty($record->contact_information['work_permit_number']))
                                        #: {{ $record->contact_information['work_permit_number'] }}<br>
                                    @endif
                                    @if(isset($record->contact_information['work_permit_emission_date']) && !empty($record->contact_information['work_permit_emission_date']))
                                        Emisión: {{ \Carbon\Carbon::parse($record->contact_information['work_permit_emission_date'])->format('m-d-Y') }}<br>
                                    @endif
                                    @if(isset($record->contact_information['work_permit_expiration_date']) && !empty($record->contact_information['work_permit_expiration_date']))
                                        Vencimiento: {{ \Carbon\Carbon::parse($record->contact_information['work_permit_expiration_date'])->format('m-d-Y') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </x-filament::section>

                @php
                    $isLifePolicy = false;
                    if ($record->policy_type === App\Enums\PolicyType::Life) {
                        $isLifePolicy = true;
                    } elseif (is_string($record->policy_type)) {
                        $isLifePolicy = $record->policy_type === 'life';
                    } elseif (is_object($record->policy_type) && property_exists($record->policy_type, 'value')) {
                        $isLifePolicy = $record->policy_type->value === 'life';
                    }
                @endphp

                @if(!$isLifePolicy)
                <x-filament::section class="bg-gray-100 mt-8" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Aplicantes Adicionales
                    </x-slot>
                    @if(isset($record->additional_applicants) && !empty($record->additional_applicants))
                        @foreach($record->additional_applicants as $index => $applicant)
                            <div style="margin-bottom: 2rem; border-bottom: 1px solid #E5E7EB; padding-bottom: 1rem;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: #4B5563;">
                                        Aplicante {{ $index + 1 }}: {{ $applicant->first_name ?? '' }} {{ $applicant->middle_name ?? '' }} {{ $applicant->last_name ?? '' }}
                                    </h3>
                                    <span style="background-color: #E5E7EB; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500;">
                                        {{ App\Enums\FamilyRelationship::tryFrom($applicant->relationship ?? '')?->getLabel() ?? $applicant->relationship ?? 'N/A' }}
                                    </span>
                                </div>

                                {{-- Personal Information --}}
                                <div style="margin-bottom: 1.5rem;">
                                    <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información Personal</h4>
                                    <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Género</p>
                                            <p class="mt-1">{{ $applicant->gender ? App\Enums\Gender::tryFrom($applicant->gender)?->getLabel() : 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Fecha de Nacimiento</p>
                                            <p class="mt-1">
                                                @if(isset($applicant->date_of_birth) && !empty($applicant->date_of_birth))
                                                    {{ \Carbon\Carbon::parse($applicant->date_of_birth)->format('m-d-Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Edad</p>
                                            <p class="mt-1">{{ $applicant->age ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Estado Civil</p>
                                            <p class="mt-1">{{ isset($applicant->marital_status) ? App\Enums\MaritialStatus::tryFrom($applicant->marital_status)?->getLabel() : 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Immigration Information --}}
                                <div>
                                    <h4 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información Migratoria</h4>
                                    <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Estado Migratorio</p>
                                            <p class="mt-1">
                                                @php
                                                    $status = isset($applicant->member_inmigration_status) ? App\Enums\ImmigrationStatus::tryFrom($applicant->member_inmigration_status) : null;
                                                @endphp
                                                {{ $status?->getLabel() }}
                                                @if($status === App\Enums\ImmigrationStatus::Other && isset($applicant->member_inmigration_status_category))
                                                    - {{ $applicant->member_inmigration_status_category }}
                                                @endif
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Identificación</p>
                                            <p class="mt-1">
                                                @if(isset($applicant->member_ssn) && !empty($applicant->member_ssn))
                                                    SSN: {{ $applicant->member_ssn }}
                                                @elseif(isset($applicant->member_passport) && !empty($applicant->member_passport))
                                                    Pasaporte: {{ $applicant->member_passport }}
                                                @elseif(isset($applicant->member_green_card_number) && !empty($applicant->member_green_card_number))
                                                    Green Card: {{ $applicant->member_green_card_number }}
                                                @elseif(isset($applicant->member_alien_number) && !empty($applicant->member_alien_number))
                                                    Alien #: {{ $applicant->member_alien_number }}
                                                @else
                                                    N/A
                                                @endif
                                            </p>
                                        </div>

                                        <div>
                                            <p class="text-sm font-medium text-gray-500">Permiso de Trabajo</p>
                                            <p class="mt-1">
                                                @if(isset($applicant->member_work_permit_number) && !empty($applicant->member_work_permit_number))
                                                    #: {{ $applicant->member_work_permit_number }}<br>
                                                @endif
                                                @if(isset($applicant->member_work_permit_emission_date) && !empty($applicant->member_work_permit_emission_date))
                                                    Emisión: {{ \Carbon\Carbon::parse($applicant->member_work_permit_emission_date)->format('m-d-Y') }}<br>
                                                @endif
                                                @if(isset($applicant->member_work_permit_expiration_date) && !empty($applicant->member_work_permit_expiration_date))
                                                    Vencimiento: {{ \Carbon\Carbon::parse($applicant->member_work_permit_expiration_date)->format('m-d-Y') }}
                                                @endif
                                                @if(empty($applicant->member_work_permit_number) && empty($applicant->member_work_permit_emission_date) && empty($applicant->member_work_permit_expiration_date))
                                                    N/A
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 italic">No hay aplicantes adicionales registrados.</p>
                    @endif
                </x-filament::section>

                <x-filament::section class="bg-gray-100 mt-8" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Aplicantes (Resumen)
                    </x-slot>
                    @php
                        // Get all applicants from the applicants collection
                        $main_applicant = $record->main_applicant;
                        $additional_applicants = $record->additional_applicants;
                        $applicants = collect([$main_applicant]);
                        if (!empty($additional_applicants)) {
                            foreach ($additional_applicants as $applicant) {
                                $applicants->push($applicant);
                            }
                        }
                    @endphp

                    @if($applicants->isNotEmpty())
                        <div style="width: 100%;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                <tr style="background-color: #f0f0f0;">
                                    <th style="width: 30%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Nombre</th>
                                    <th style="width: 15%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Relación</th>
                                    <th style="width: 8%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Edad</th>
                                    <th style="width: 15%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Género</th>
                                    <th style="width: 10%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Fuma</th>
                                    <th style="width: 10%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Embarazada</th>
                                    <th style="width: 15%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Elegible</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($applicants as $index => $applicant)
                                    @if($applicant)
                                        <tr>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                                {{ $applicant->first_name ?? '' }} {{ $applicant->middle_name ?? '' }} {{ $applicant->last_name ?? '' }}
                                            </td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                                @if($index === 0 || $applicant->relationship === 'Aplicante Principal' || $applicant->relationship === 'self')
                                                    Aplicante Principal
                                                @else
                                                    @php
                                                        try {
                                                            $relation = App\Enums\FamilyRelationship::tryFrom($applicant->relationship)?->getLabel() ?? $applicant->relationship ?? 'N/A';
                                                        } catch (\Exception $e) {
                                                            $relation = $applicant->relationship ?? 'N/A';
                                                        }
                                                    @endphp
                                                    {{ $relation }}
                                                @endif
                                            </td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $applicant->age ?? 'N/A' }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                                {{ $applicant->gender ? App\Enums\Gender::tryFrom($applicant->gender)?->getLabel() : 'N/A' }}
                                            </td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ isset($applicant->is_tobacco_user) && $applicant->is_tobacco_user ? 'Sí' : 'No' }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ isset($applicant->is_pregnant) && $applicant->is_pregnant ? 'Sí' : 'No' }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ isset($applicant->is_eligible_for_coverage) && $applicant->is_eligible_for_coverage ? 'Sí' : 'No' }}</td>
                                        </tr>
                                    @endif
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
                            return $applicant && ($applicant->yearly_income > 0) || ($applicant?->self_employed_yearly_income > 0);
                        });
                        // dd($applicants);
                    @endphp

                    @if($applicantsIncome->isNotEmpty())
                        <div style="width: 100%;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                <tr style="background-color: #f0f0f0;">
                                    <th style="width: 30%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Nombre</th>
                                    <th style="width: 15%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Relación</th>
                                    <th style="width: 8%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Hora $</th>
                                    <th style="width: 8%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">H x S</th>
                                    <th style="width: 8%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">HE $</th>
                                    <th style="width: 8%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">HE x S</th>
                                    <th style="width: 8%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">S x A</th>
                                    <th style="width: 15%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Ingresos</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($applicantsIncome as $index => $applicant)
                                    <tr>
                                        @if(!$applicant->is_self_employed)
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                                @if($index === 0)
                                                    {{ $record->contact->full_name }}
                                                @else
                                                    {{ $applicant->first_name ?? '' }} {{ $applicant->middle_name ?? '' }} {{ $applicant->last_name ?? '' }}
                                                @endif
                                            </td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                                @if($index === 0 || $applicant->relationship === 'Aplicante Principal' || $applicant->relationship === 'self')
                                                    Aplicante Principal
                                                @else
                                                    @php
                                                        try {
                                                            $relation = App\Enums\FamilyRelationship::tryFrom($applicant->relationship)?->getLabel() ?? $applicant->relationship ?? 'N/A';
                                                        } catch (\Exception $e) {
                                                            $relation = $applicant->relationship ?? 'N/A';
                                                        }
                                                    @endphp
                                                    {{ $relation }}
                                                @endif
                                            </td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $applicant->income_per_hour ?? 'N/A' }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $applicant->hours_per_week }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $applicant->income_per_extra_hour }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $applicant->extra_hours_per_week }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $applicant->weeks_per_year }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ number_format($applicant->yearly_income, 2) }}</td>
                                        @else
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                                @if($index === 0)
                                                    {{ $record->contact->full_name }}
                                                @else
                                                    {{ $applicant->first_name ?? '' }} {{ $applicant->middle_name ?? '' }} {{ $applicant->last_name ?? '' }}
                                                @endif
                                            </td>
                                            @php
                                                try {
                                                    if ($index === 0 || $applicant->relationship === 'self') {
                                                        $relation = 'Aplicante Principal';
                                                    } else {
                                                        $relation = App\Enums\FamilyRelationship::tryFrom($applicant->relationship)?->getLabel() ?? $applicant->relationship ?? 'N/A';
                                                    }
                                                } catch (\Exception $e) {
                                                    $relation = ($index === 0 || $applicant->relationship === 'self') ? 'Aplicante Principal' : ($applicant->relationship ?? 'N/A');
                                                }
                                            @endphp

                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $relation }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: right;" colspan="4">Self-Employed</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $applicant->self_employed_profession ?? 'N/A' }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ number_format($applicant->self_employed_yearly_income, 2) }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: right;" colspan="6">Total Ingresos</td>
                                    <td style="padding: 0.5rem; border: 1px solid #ddd;">$ {{ number_format($record->estimated_household_income) }}</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif

                </x-filament::section>

                <x-filament::section class="bg-gray-100 mt-10" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Pago
                    </x-slot>

                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información General</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Pago Recurrente</p>
                                <p class="mt-1">{{ $record->recurring_payment ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Fecha del Primer Pago</p>
                                <p class="mt-1">{{ $record->first_payment_date ? \Carbon\Carbon::parse($record->first_payment_date)->format('m-d-Y') : 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Fecha de Pago Preferida</p>
                                <p class="mt-1">{{ $record->preferred_payment_day ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    @if($record->payment_card_type || $record->payment_card_number)
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Tarjeta</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Tipo de Tarjeta</p>
                                <p class="mt-1">
                                    @php
                                        $cardTypes = [
                                            'visa' => 'Visa',
                                            'master' => 'Mastercard',
                                            'amex' => 'American Express',
                                            'discover' => 'Discover',
                                            'diners' => 'Diners Club',
                                            'otro' => 'Otro',
                                        ];
                                    @endphp
                                    {{ $record->payment_card_type ? ($cardTypes[$record->payment_card_type] ?? $record->payment_card_type) : 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Banco</p>
                                <p class="mt-1">{{ $record->payment_card_bank ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Titular</p>
                                <p class="mt-1">{{ $record->payment_card_holder ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Número</p>
                                <p class="mt-1">
                                    @if($record->payment_card_number)
                                        {{ $record->payment_card_number }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Vencimiento</p>
                                <p class="mt-1">
                                    @if($record->payment_card_exp_month && $record->payment_card_exp_year)
                                        {{ $record->payment_card_exp_month }}/{{ $record->payment_card_exp_year }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($record->payment_bank_account_bank || $record->payment_bank_account_number)
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Cuenta Bancaria</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Banco</p>
                                <p class="mt-1">{{ $record->payment_bank_account_bank ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Titular</p>
                                <p class="mt-1">{{ $record->payment_bank_account_holder ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">ABA / Routing</p>
                                <p class="mt-1">{{ $record->payment_bank_account_aba ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Cuenta</p>
                                <p class="mt-1">
                                    @if($record->payment_bank_account_number)
                                        {{ $record->payment_bank_account_number }}
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($record->billing_address_1)
                    <div style="margin-bottom: 1.5rem;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Dirección de Facturación</h3>
                        <div style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="mt-1">
                                    {{ $record->billing_address_1 }}<br>
                                    @if($record->billing_address_2)
                                        {{ $record->billing_address_2 }}<br>
                                    @endif
                                    {{ $record->billing_address_city }}, {{ $record->billing_address_state }} {{ $record->billing_address_zip }}
                                </p>
                            </div>
                        </div>
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
                            <div style="width: 100%;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                    <tr style="background-color: #f0f0f0;">
                                        <th style="width: 40%; padding: 0.5rem; border: 1px solid #ddd; text-align: left;">Aplicante</th>
                                        <th style="width: 20%; padding: 0.5rem; border: 1px solid #ddd; text-align: center;">Medicamento</th>
                                        <th style="width: 20%; padding: 0.5rem; border: 1px solid #ddd; text-align: center;">Dosis</th>
                                        <th style="width: 20%; padding: 0.5rem; border: 1px solid #ddd; text-align: center;">Meses</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {{-- @foreach($record->prescription_drugs as $drug)
                                        @php
                                            // dd($record->additional_applicants);
                                        @endphp
                                        <tr>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $drug['applicant'] === 'main' ? 'Principal' : $drug['applicant'] }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">{{ $drug['name'] }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">{{ $drug['dosage'] }}</td>
                                            <td style="padding: 0.5rem; border: 1px solid #ddd; text-align: center;">{{ $drug['frequency'] }}</td>
                                        </tr>
                                    @endforeach --}}
                                    </tbody>
                                </table>
                            </div>
                        @endif

                    </x-filament::section>

                @endIf

                @else
                {{-- Life Insurance Policy Section --}}
                <x-filament::section class="bg-gray-100 mt-8" style="margin-top: 2.5rem !important;">
                    <x-slot name="heading">
                        Información del Seguro de Vida
                    </x-slot>

                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información Física</h3>
                        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Altura (cm)</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['height_cm'] ?? 'N/A' }}</p>
                                <p class="text-sm font-medium text-gray-500 mt-3">Peso (kg)</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['weight_kg'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Altura (pies)</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['height_feet'] ?? 'N/A' }}</p>
                                <p class="text-sm font-medium text-gray-500 mt-3">Peso (lb)</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['weight_lbs'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información Adicional</h3>
                        <div style="display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Fumador</p>
                                <p class="mt-1">{{ isset($record->life_insurance['applicant']['smoker']) && $record->life_insurance['applicant']['smoker'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Deportes extremos</p>
                                <p class="mt-1">{{ isset($record->life_insurance['applicant']['practice_extreme_sport']) && $record->life_insurance['applicant']['practice_extreme_sport'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Felonía</p>
                                <p class="mt-1">{{ isset($record->life_insurance['applicant']['has_made_felony']) && $record->life_insurance['applicant']['has_made_felony'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Bancarrota</p>
                                <p class="mt-1">{{ isset($record->life_insurance['applicant']['has_declared_bankruptcy']) && $record->life_insurance['applicant']['has_declared_bankruptcy'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Viajes al extranjero</p>
                                <p class="mt-1">{{ isset($record->life_insurance['applicant']['plans_to_travel_abroad']) && $record->life_insurance['applicant']['plans_to_travel_abroad'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Permite videollamada</p>
                                <p class="mt-1">{{ isset($record->life_insurance['applicant']['allows_videocall']) && $record->life_insurance['applicant']['allows_videocall'] ? 'Sí' : 'No' }}</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280; ">Información Médica</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div style="">
                                <p class="text-sm font-medium text-gray-500">Doctor Principal</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['primary_doctor'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Teléfono Doctor</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['primary_doctor_phone'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Dirección del Doctor</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['primary_doctor_address'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Fecha Diagnóstico</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['diagnosis_date'] ?? 'N/A' }}</p>
                            </div>
                            <div style="grid-column: span 2 / span 2;">
                                <p class="text-sm font-medium text-gray-500">Diagnóstico</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['diagnosis'] ?? 'N/A' }}</p>
                            </div>
                        </div>


                        @if(isset($record->life_insurance['applicant']['has_been_hospitalized']) && $record->life_insurance['applicant']['has_been_hospitalized'])
                        <div style="margin-top: 1rem; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Ha sido hospitalizado</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['has_been_hospitalized'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Fecha hospitalización</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['hospitalized_date'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Enfermedades</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['disease'] ?? 'Ninguna' }}</p>
                            </div>
                            <div style="grid-column: span 3 / span 3;">
                                <p class="text-sm font-medium text-gray-500">Medicamentos Prescritos</p>
                                <p class="mt-1">{{ $record->life_insurance['applicant']['drugs_prescribed'] ?? 'Ninguno' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información Familiar</h3>
                        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Padre Fallecido</p>
                                <p class="mt-1">{{ isset($record->life_insurance['father']['is_alive']) && $record->life_insurance['father']['is_alive'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Edad Padre</p>
                                <p class="mt-1">{{ $record->life_insurance['father']['age'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Motivo Fallecimiento</p>
                                <p class="mt-1">{{ isset($record->life_insurance['father']['is_alive']) && $record->life_insurance['father']['is_alive'] ? $record->life_insurance['father']['death_reason'] ?? 'N/A' : '' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Madre Fallecida</p>
                                <p class="mt-1">{{ isset($record->life_insurance['mother']['is_alive']) && $record->life_insurance['mother']['is_alive'] ? 'Sí' : 'No' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Edad Madre</p>
                                <p class="mt-1">{{ $record->life_insurance['mother']['age'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Motivo Fallecimiento</p>
                                <p class="mt-1">{{ isset($record->life_insurance['mother']['is_alive']) && $record->life_insurance['mother']['is_alive'] ? $record->life_insurance['mother']['death_reason'] ?? 'N/A' : '' }}</p>
                            </div>
                        </div>

                        @if(isset($record->life_insurance['family']['member_final_disease']) && $record->life_insurance['family']['member_final_disease'])
                        <div style="margin-top: 1rem; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Familiar con Enfermedad</p>
                                <p class="mt-1">Sí</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Parentesco</p>
                                <p class="mt-1">{{ $record->life_insurance['family']['member_final_disease_relationship'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Enfermedad</p>
                                <p class="mt-1">{{ $record->life_insurance['family']['member_final_disease_description'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Información Laboral</h3>
                        <div style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem;">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Empleador</p>
                                <p class="mt-1">{{ $record->life_insurance['employer']['name'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Cargo</p>
                                <p class="mt-1">{{ $record->life_insurance['employer']['job_title'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Teléfono Trabajo</p>
                                <p class="mt-1">{{ $record->life_insurance['employer']['employment_phone'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Fecha Inicio</p>
                                <p class="mt-1">{{ $record->life_insurance['employer']['employment_start_date'] ?? 'N/A' }}</p>
                            </div>
                            <div style="grid-column: span 4 / span 4;">
                                <p class="text-sm font-medium text-gray-500">Dirección Trabajo</p>
                                <p class="mt-1">{{ $record->life_insurance['employer']['employment_address'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Patrimonio</h3>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Patrimonio</p>
                            <p class="mt-1">
                                @if(isset($record->life_insurance['applicant']['patrimony']) && $record->life_insurance['applicant']['patrimony'] !== null)
                                    ${{ number_format($record->life_insurance['applicant']['patrimony'], 2) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>

                    @if(isset($record->life_insurance['beneficiaries']) && isset($record->life_insurance['total_beneficiaries']))
                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Beneficiarios</h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 0.5rem;">
                            <thead>
                                <tr>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Nombre</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Parentesco</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Fecha Nacimiento</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 1; $i <= $record->life_insurance['total_beneficiaries']; $i++)
                                    @if(isset($record->life_insurance['beneficiaries'][$i]))
                                    <tr>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $record->life_insurance['beneficiaries'][$i]['name'] ?? 'N/A' }}</td>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                            @php
                                                $relationship = isset($record->life_insurance['beneficiaries'][$i]['relationship']) 
                                                    ? FamilyRelationship::tryFrom($record->life_insurance['beneficiaries'][$i]['relationship']) 
                                                    : null;
                                            @endphp
                                            {{ $relationship ? $relationship->getLabel() : 'N/A' }}
                                        </td>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $record->life_insurance['beneficiaries'][$i]['date_of_birth'] ?? 'N/A' }}</td>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $record->life_insurance['beneficiaries'][$i]['percentage'] ?? '0' }}%</td>
                                    </tr>
                                    @endif
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if(isset($record->life_insurance['contingents']) && isset($record->life_insurance['total_contingents']) && $record->life_insurance['total_contingents'] > 0)
                    <div style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
                        <h3 style="font-size: 1rem; font-weight: 600; margin-bottom: 0.75rem; color: #6B7280;">Beneficiarios Contingentes</h3>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 0.5rem;">
                            <thead>
                                <tr>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Nombre</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Parentesco</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Fecha Nacimiento</th>
                                    <th style="padding: 0.5rem; border: 1px solid #ddd; background-color: #f3f4f6; text-align: left;">Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($i = 1; $i <= $record->life_insurance['total_contingents']; $i++)
                                    @if(isset($record->life_insurance['contingents'][$i]))
                                    <tr>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $record->life_insurance['contingents'][$i]['name'] ?? 'N/A' }}</td>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">
                                            @php
                                                $relation = FamilyRelationship::tryFrom($record->life_insurance['contingents'][$i]['relationship']);
                                            @endphp
                                            {{ $relation ? $relation->getLabel() : 'N/A' }}
                                        </td>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $record->life_insurance['contingents'][$i]['date_of_birth'] ?? 'N/A' }}</td>
                                        <td style="padding: 0.5rem; border: 1px solid #ddd;">{{ $record->life_insurance['contingents'][$i]['percentage'] ?? '0' }}%</td>
                                    </tr>
                                    @endif
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    @endif
                </x-filament::section>
                @endif

            </x-filament::section>

        </div>
    </div>
</x-filament-panels::page>
