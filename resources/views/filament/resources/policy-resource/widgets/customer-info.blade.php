<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            {{-- Policy Details --}}
            <div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Póliza</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white font-semibold">{{ $record->code }}</p>
                </div>
                <div class="mt-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Tipo</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->policy_type->getLabel() }}</p>
                </div>
            </div>

            {{-- Status and Dates --}}
            <div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Estatus</h3>
                    <p class="mt-1">
                        <span class="inline-flex items-center py-0.5">
                            <x-filament::badge :color="$record->status->getColor()">
                                {{ $record->status->getLabel() }}
                            </x-filament::badge>
                        </span>
                    </p>
                </div>
                <div class=" mt-2">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Inicio</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $record->effective_date->format('m/d/Y') }}
                    </p>
                </div>
            </div>

            {{-- Applicants --}}
            <div>
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aplicantes</h3>
                <ul class="mt-2 space-y-2 text-sm text-gray-900 dark:text-white">
                    @foreach($record->policyApplicants as $applicant)
                        <li>{{ $applicant->contact?->full_name }} @if($applicant->medicaid_client)
                        <span class="text-xs text-gray-500 dark:text-gray-400">(Medicaid)</span> @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>