<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\Pages;

use App\Concerns\HasTabSpecificColumnToggles;
use App\Enums\Accounting\InvoiceStatus;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Filament\Company\Resources\Sales\InvoiceResource\Widgets;
use App\Filament\Company\Resources\Sales\RecurringInvoiceResource\Pages\ViewRecurringInvoice;
use App\Filament\Infolists\Components\BannerEntry;
use App\Models\Accounting\RecurringInvoice;
use Filament\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;

class ListInvoices extends ListRecords
{
    use ExposesTableToWidgets;
    use HasTabSpecificColumnToggles;

    protected static string $resource = InvoiceResource::class;

    #[Url(except: '')]
    public string $recurringInvoice = '';

    protected static string $view = 'filament.company.resources.sales.invoice-resource.pages.list-invoices';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                BannerEntry::make('recurringInvoiceFilter')
                    ->info()
                    ->title(function () {
                        if (empty($this->recurringInvoice)) {
                            return null;
                        }

                        $recurringInvoice = RecurringInvoice::find($this->recurringInvoice);

                        $clientName = $recurringInvoice?->client?->name;

                        if (! $clientName) {
                            return __('You are currently viewing invoices created from a recurring invoice');
                        }

                        $recurringInvoiceUrl = ViewRecurringInvoice::getUrl([
                            'record' => $recurringInvoice,
                        ]);

                        $link = Blade::render('filament::components.link', [
                            'href' => $recurringInvoiceUrl,
                            'slot' => __('a recurring invoice for :clientName', ['clientName' => $clientName]),
                        ]);

                        return new HtmlString(
                            __('You are currently viewing invoices created from :link', ['link' => $link])
                        );
                    })
                    ->visible(fn () => ! empty($this->recurringInvoice))
                    ->actions([
                        Action::make('clearFilter')
                            ->label(__('Clear filter'))
                            ->button()
                            ->outlined()
                            ->action('clearFilter'),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('recordPayments')
                ->label(__('Record payments'))
                ->outlined()
                ->url(RecordPayments::getUrl()),
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            Widgets\InvoiceOverview::make(),
        ];
    }

    public function clearFilter(): void
    {
        $this->recurringInvoice = '';
        $this->tableFilters = []; // Refresh widgets/table
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label(__('All')),

            'unpaid' => Tab::make()
                ->label(__('Unpaid'))
                ->modifyQueryUsing(function (Builder $query) {
                    $query->unpaid();
                }),

            'draft' => Tab::make()
                ->label(__('Draft'))
                ->modifyQueryUsing(function (Builder $query) {
                    $query->where('status', InvoiceStatus::Draft);
                }),
        ];
    }
}
