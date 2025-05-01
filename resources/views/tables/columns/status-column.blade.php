<div class="py-4 px-4">
{{--    {{ $getState() }}--}}
    <p class="text-sm">
        Estatus: <span class="text-color-{{ $getRecord()->status->getColor() }}">{{ $getRecord()->status->getLabel() }}</span>
    </p>
    <p class="text-sm mt-2">
        Documentos: <span class="text-color-{{ $getRecord()->document_status->getColor() }}">{{ $getRecord()->document_status->getLabel() }}</span> 
        @php 
            if ($getRecord()->document_status === App\Enums\DocumentStatus::Pending) {
                echo ' / Vence: ' . $getRecord()->next_document_expiration_date?->format('m-d-Y');
            }
        @endphp
    </p>

    {{-- 5 columns for Informado, Autopay, Inicial, ACA, FPL with Green Checkmark Icon if true and Red X Icon if false --}}
    <div class="flex gap-2 mt-2">
        <div class="flex items-center gap-4 text-sm">
            <span class="flex items-center gap-1">
                Informado 
                @if ($getRecord()->client_notified)
                    <x-iconoir-check-circle class="text-color-success h-5 w-5"/>
                @else
                    <x-iconoir-xmark-circle class="text-color-danger h-5 w-5"/>
                @endif
            </span>
            <span class="flex items-center gap-1">
                Autopay 
                @if ($getRecord()->autopay)
                    <x-iconoir-check-circle class="text-color-success h-5 w-5"/>
                @else
                    <x-iconoir-xmark-circle class="text-color-danger h-5 w-5"/>
                @endif
            </span>
            <span class="flex items-center gap-1">
                Inicial 
                @if ($getRecord()->initial_paid)
                    <x-iconoir-check-circle class="text-color-success h-5 w-5"/>
                @else
                    <x-iconoir-xmark-circle class="text-color-danger h-5 w-5"/>
                @endif
            </span>
            @if ($getRecord()->requires_aca)
                <span class="flex items-center gap-1">
                    ACA
                    @if ($getRecord()->aca)
                        <x-iconoir-check-circle class="text-color-success h-5 w-5"/>
                    @else
                        <x-iconoir-xmark-circle class="text-color-danger h-5 w-5"/>
                    @endif
                </span>
            {{-- @else
                <span class="flex items-center gap-1">
                    <mdi-circle-off-outline class="text-color-danger h-5 w-5"/>
                </span> --}}
            @endif
            <span class="flex items-center gap-1">
                Ingresos 
                @php
                    // Get the latest KynectFPL record
                    $latestFPL = \App\Models\KynectFPL::latest()->first();
                    $meetsFPL = false;
                    
                    if ($latestFPL) {
                        $policy = $getRecord();
                        $householdSize = $policy->total_family_members;
                        $annualIncome = (float) $policy->estimated_household_income;
                        
                        // Calculate threshold based on household size
                        $threshold = null;
                        if ($householdSize <= 8) {
                            $memberField = "members_{$householdSize}";
                            $threshold = $latestFPL->{$memberField} * 12;
                        } else {
                            $baseAmount = $latestFPL->members_8;
                            $extraMembers = $householdSize - 8;
                            $threshold = ($baseAmount + ($latestFPL->additional_member * $extraMembers)) * 12;
                        }
                        
                        // Check if meets requirement
                        $meetsFPL = $annualIncome >= $threshold;
                    }
                @endphp
                
                @if ($meetsFPL)
                    <x-iconoir-check-circle class="text-color-success h-5 w-5"/>
                @else
                    <x-iconoir-xmark-circle class="text-color-danger h-5 w-5"/>
                @endif
            </span>
        </div>
    </div>

</div>