<div class="text-sm">
    @if ($getRecord()->ssn != '')
        SSN: {{ $getRecord()->ssn }}
    @elseif ($getRecord()->alien_number != '')
        Alien #: {{ $getRecord()->alien_number }}
    @elseif ($getRecord()->passport_number != '')
        Pasaporte: {{ $getRecord()->passport_number }}
    @endif
</div>