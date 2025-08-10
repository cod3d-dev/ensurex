<x-filament-panels::page>
    <x-filament-panels::form wire:submit="findPolicies">
        {{ $this->form }}

        <x-filament-panels::form.actions :actions="[
        \Filament\Actions\Action::make('findPolicies')
            ->label('Find Policies')
            ->action('findPolicies')
    ]" />
    </x-filament-panels::form>

    @if($user_id && $until_date)
        <div class="mt-6">
            {{ $this->table }}
        </div>

        <div class="mt-4">
            <div class="text-lg font-bold">
                Select policies using the checkboxes and use the bulk action to generate a commission statement.
            </div>
        </div>
    @endif
</x-filament-panels::page>