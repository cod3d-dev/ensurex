<div class="space-y-4">
    @if($documents->isEmpty())
        <p class="text-center text-gray-500">No hay documentos registrados para esta póliza.</p>
    @else
        @foreach($documents as $document)
            @php
                $statusColor = match($document->status) {
                    \App\Enums\DocumentStatus::Rejected => 'text-danger-600 bg-danger-50',
                    \App\Enums\DocumentStatus::Expired => 'text-danger-600 bg-danger-50',
                    \App\Enums\DocumentStatus::Pending => 'text-warning-600 bg-warning-50',
                    \App\Enums\DocumentStatus::Sent => 'text-info-600 bg-info-50',
                    \App\Enums\DocumentStatus::Approved => 'text-success-600 bg-success-50',
                    \App\Enums\DocumentStatus::ToAdd => 'text-gray-600 bg-gray-50',
                    default => 'text-gray-600 bg-gray-50',
                };
                $docType = $document->documentType ? $document->documentType->name : 'Documento';
                $dueDate = $document->due_date ? date('m-d-Y', strtotime($document->due_date)) : 'Sin fecha';
                $sentDate = $document->sent_date ? date('m-d-Y', strtotime($document->sent_date)) : '-';
            @endphp
            
            <div class="p-4 rounded-lg border border-gray-200">
                <div class="flex justify-between items-center">
                    <span class="font-medium">{{ $document->name ?? 'Sin nombre' }}</span>
                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $statusColor }}">{{ $document->status->getLabel() }}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600">Tipo: {{ $docType }}</div>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <div class="text-sm">Vencimiento: <span class="text-gray-900">{{ $dueDate }}</span></div>
                    <div class="text-sm">Enviado: <span class="text-gray-900">{{ $sentDate }}</span></div>
                </div>
            </div>
        @endforeach
    @endif

    @if(!$documents->isEmpty())
    <div class="flex justify-end mt-4">
        <a href="{{ \App\Filament\Resources\PolicyResource::getUrl('documents', ['record' => $documents->first()->policy_id]) }}" class="filament-button filament-button-size-md inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset min-h-[2.25rem] px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700">
            Ver más detalles
        </a>
    </div>
    @endif
</div>
