<x-filament-panels::page>
    <x-filament-panels::form wire:submit="findPolicies">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="[
        \Filament\Actions\Action::make('findPolicies')
            ->label('Buscar Pólizas')
            ->action('findPolicies')
    ]" />
    </x-filament-panels::form>

    {{-- @if($asistant_id && $until_date)
    <div class="mt-6"> --}}
        {{ $this->table }}
    </div>

    {{-- <div class="mt-4">
        <div class="text-lg">
            Selecciona las pólizas y usa la acción de lote para generar una declaración de comisiones.
        </div>
    </div> --}}
    {{-- @endif --}}
</x-filament-panels::page>