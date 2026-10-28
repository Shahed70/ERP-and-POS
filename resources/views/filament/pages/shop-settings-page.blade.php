<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex justify-end gap-3 pt-4">
            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                {{ __('Save Settings (সেটিংস সংরক্ষণ করুন)') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
