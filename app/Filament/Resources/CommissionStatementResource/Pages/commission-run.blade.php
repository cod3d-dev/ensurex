<x-filament-panels::page>
    <x-filament-panels::form wire:submit="findPolicies">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="[
        \Filament\Actions\Action::make('findPolicies')
            ->label('Find Policies')
            ->submit()
    ]" />
    </x-filament-panels::form>

    @if($agent_id && $until_date)
        <div class="mt-6">
            {{ $this->table }}
        </div>

        <div class="mt-4 flex justify-between items-center">
            <div class="text-lg font-bold">
                Total Commission: ${{ number_format($totalCommission, 2) }}
            </div>

            <x-filament::button wire:click="generateStatement" type="button" color="success">
                Generate Commission Statement
            </x-filament::button>
        </div>
    @endif
</x-filament-panels::page>