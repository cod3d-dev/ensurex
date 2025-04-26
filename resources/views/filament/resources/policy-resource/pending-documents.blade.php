@if($documents->isEmpty())
    <div class="text-center py-4 text-gray-500">
        No hay documentos pendientes.
    </div>
@else
    <div class="space-y-4">
        @foreach($documents as $document)
            <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0">
                    <x-heroicon-o-document-text class="w-6 h-6 text-gray-400"/>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                        {{ $document->name }}
                    </p>
                    <p class="text-sm text-gray-500">
                        Vence: {{ $document->due_date ? (is_string($document->due_date) ? $document->due_date : $document->due_date->format('d/m/Y')) : 'Sin fecha' }}
                    </p>
                    @if($document->notes)
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $document->notes }}
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
