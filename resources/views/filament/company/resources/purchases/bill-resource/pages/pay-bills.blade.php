<x-filament-panels::page
    @class([
        'fi-resource-list-records-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
    ])
>
    <div class="flex flex-col gap-y-6">
        <x-filament::section>
            <div class="flex items-center justify-between">
                <div>
                    {{ $this->form }}
                </div>
                <div class="text-right">
                    <div class="text-sm uppercase text-gray-500 dark:text-gray-400">Total Payment Amount</div>
                    <div class="text-3xl font-semibold text-gray-900 dark:text-white">{{ $this->totalPaymentAmount }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament-panels::resources.tabs />

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE, scopes: $this->getRenderHookScopes()) }}

        {{ $this->table }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER, scopes: $this->getRenderHookScopes()) }}
    </div>
</x-filament-panels::page>
