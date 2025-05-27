@php
$policies = $getState();
@endphp
<div>
    @if(is_array($policies) && count($policies) > 0)
    <div class="flex flex-wrap gap-1">
        @foreach($policies as $policy)
        <x-filament::badge :color="App\Enums\PolicyType::from($policy)->getColor()">
            {{ App\Enums\PolicyType::from($policy)->getLabel() }}
        </x-filament::badge>
        @endforeach
    </div>
    @elseif(is_array($policies) && count($policies) === 0)
    <span class="text-gray-500">No policies</span>
    @else
    {{ $policies }}
    @endif
</div>