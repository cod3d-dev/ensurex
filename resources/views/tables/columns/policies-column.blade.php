@php
$policies = $getState();
@endphp
<div>
    @if(is_array($policies) && count($policies) > 0)
    <div class="flex flex-wrap gap-1">
        @foreach($policies as $policy)
        <span
            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-primary-50 text-primary-700">
            {{ $policy }}
        </span>
        @endforeach
    </div>
    @elseif(is_array($policies) && count($policies) === 0)
    <span class="text-gray-500">No policies</span>
    @else
    {{ $policies }}
    @endif
</div>