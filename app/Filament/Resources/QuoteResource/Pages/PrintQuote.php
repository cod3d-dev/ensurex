<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Enums\QuoteStatus;
use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\QuoteResource;
use App\Models\QuoteDocument;
use App\Services\QuoteConversionService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PrintQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected static string $view = 'filament.resources.quote.print';

    protected ?string $heading = '';

    public ?array $data = [];

    public $mode = 'view';

    public function mount(string|int $record): void
    {
        parent::mount($record);

        // Ensure documents are loaded
        if ($this->record) {
            $this->record->load('quoteDocuments');
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->live()
                    ->label('Nombre del Documento'),
                Forms\Components\FileUpload::make('document')
                    ->required()
                    ->live()
                    ->label('Documento')
                    ->disk('public')
                    ->directory('quote-documents/'.$this->record->id)
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(5120)
                    ->imagePreviewHeight('100')
                    ->downloadable(),
            ])
            ->statePath('data');
    }

    public function uploadDocument(): void
    {
        $data = $this->form->getState();
        $file = Storage::disk('public')->get($data['document']);

        $document = new QuoteDocument([
            'quote_id' => $this->record->id,
            'user_id' => Auth::id(),
            'name' => $data['name'],
            'file_path' => $data['document'],
            'mime_type' => Storage::disk('public')->mimeType($data['document']),
            'file_size' => Storage::disk('public')->size($data['document']),
        ]);

        $document->save();

        $this->form->fill();

        Notification::make()
            ->success()
            ->title('Documento subido exitosamente')
            ->send();
    }

    public function deleteDocument($documentId): void
    {
        $document = QuoteDocument::find($documentId);

        if ($document && $document->quote_id === $this->record->id) {
            $document->delete();

            Notification::make()
                ->success()
                ->title('Documento eliminado exitosamente')
                ->send();
        }
    }

    public function changeQuoteStatusToSent(): void
    {
        $this->record->status = QuoteStatus::Sent;
        $this->record->save();

        Notification::make()
            ->success()
            ->title('Estado de la cotización actualizado a Enviada')
            ->send();
    }
    
    public function convertToPolicy(): void
    {
        $conversionService = app(QuoteConversionService::class);
        $policy = $conversionService->convertQuoteToPolicy($this->record);
        
        Notification::make()
            ->success()
            ->title('Cotización convertida a póliza exitosamente')
            ->send();
            
        $this->redirect(PolicyResource::getUrl('edit', ['record' => $policy->id]));
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Extract main applicant and additional applicants from the applicants collection
        if (isset($data['applicants'])) {
            $applicants = collect($data['applicants']);

            // Get main applicant (first one with is_main = true, or fallback to first)
            $data['main_applicant'] = $applicants->first(fn ($a) => $a['is_main'] ?? false)
                ?? $applicants->first()
                ?? [];

            // Get additional applicants (all except main)
            $data['additional_applicants'] = $applicants
                ->filter(fn ($a) => ! ($a['is_main'] ?? false))
                ->values()
                ->all();
        }

        return $data;
    }
}
