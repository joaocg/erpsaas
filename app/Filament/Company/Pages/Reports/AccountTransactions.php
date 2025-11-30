<?php

namespace App\Filament\Company\Pages\Reports;

use App\Contracts\ExportableReport;
use App\DTO\ReportDTO;
use App\Filament\Company\Resources\Accounting\TransactionResource;
use App\Models\Accounting\Account;
use App\Models\Common\Client;
use App\Models\Common\Vendor;
use App\Services\ExportService;
use App\Services\ReportService;
use App\Support\Column;
use App\Transformers\AccountTransactionReportTransformer;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Guava\FilamentClusters\Forms\Cluster;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountTransactions extends BaseReportPage
{
    protected static string $view = 'filament.company.pages.reports.account-transactions';

    protected ReportService $reportService;

    protected ExportService $exportService;

    public function boot(ReportService $reportService, ExportService $exportService): void
    {
        $this->reportService = $reportService;
        $this->exportService = $exportService;
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }

    protected function initializeDefaultFilters(): void
    {
        if (empty($this->getFilterState('selectedAccount'))) {
            $this->setFilterState('selectedAccount', 'all');
        }

        if (empty($this->getFilterState('selectedPayee'))) {
            $this->setFilterState('selectedPayee', 'all');
        }
    }

    /**
     * @return array<Column>
     */
    public function getTable(): array
    {
        return [
            Column::make('date')
                ->label(__('Date'))
                ->markAsDate()
                ->alignment(Alignment::Left),
            Column::make('description')
                ->label(__('Description'))
                ->alignment(Alignment::Left),
            Column::make('debit')
                ->label(__('Debit'))
                ->alignment(Alignment::Right),
            Column::make('credit')
                ->label(__('Credit'))
                ->alignment(Alignment::Right),
            Column::make('balance')
                ->label(__('Running balance'))
                ->alignment(Alignment::Right),
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->columns(5)
            ->schema([
                Select::make('selectedAccount')
                    ->label(__('Account'))
                    ->options($this->getAccountOptions())
                    ->selectablePlaceholder(false)
                    ->searchable(),
                $this->getDateRangeFormComponent(),
                Cluster::make([
                    $this->getStartDateFormComponent(),
                    $this->getEndDateFormComponent(),
                ])->extraFieldWrapperAttributes([
                    'class' => 'report-hidden-label',
                ]),
                Select::make('selectedPayee')
                    ->label(__('Payee'))
                    ->options($this->getEntityOptions())
                    ->searchable()
                    ->selectablePlaceholder(false),
                Actions::make([
                    Actions\Action::make('applyFilters')
                        ->label(__('Update report'))
                        ->action('applyFilters')
                        ->keyBindings(['mod+s'])
                        ->button(),
                ])->alignEnd()->verticallyAlignEnd(),
            ]);
    }

    protected function getAccountOptions(): array
    {
        $accounts = Account::query()
            ->get()
            ->groupBy(fn (Account $account) => $account->category->getPluralLabel())
            ->map(fn (Collection $accounts) => $accounts->pluck('name', 'id'))
            ->toArray();

        $allAccountsOption = [
            __('All accounts') => ['all' => __('All accounts')],
        ];

        return $allAccountsOption + $accounts;
    }

    protected function getEntityOptions(): array
    {
        $clients = Client::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $vendors = Vendor::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [-$id => $name])
            ->toArray();

        $allEntitiesOption = [
            __('All payees') => ['all' => __('All payees')],
        ];

        return $allEntitiesOption + [
            __('Clients') => $clients,
            __('Vendors') => $vendors,
        ];
    }

    protected function buildReport(array $columns): ReportDTO
    {
        return $this->reportService->buildAccountTransactionsReport(
            startDate: $this->getFormattedStartDate(),
            endDate: $this->getFormattedEndDate(),
            columns: $columns,
            accountId: $this->getFilterState('selectedAccount'),
            entityId: $this->getFilterState('selectedPayee'),
        );
    }

    protected function getTransformer(ReportDTO $reportDTO): ExportableReport
    {
        return new AccountTransactionReportTransformer($reportDTO);
    }

    public function exportCSV(): StreamedResponse
    {
        return $this->exportService->exportToCsv($this->company, $this->report, $this->getFilterState('startDate'), $this->getFilterState('endDate'));
    }

    public function exportPDF(): StreamedResponse
    {
        return $this->exportService->exportToPdf($this->company, $this->report, $this->getFilterState('startDate'), $this->getFilterState('endDate'));
    }

    public function getEmptyStateHeading(): string | Htmlable
    {
        return __('No transactions found');
    }

    public function getEmptyStateDescription(): string | Htmlable | null
    {
        return __('Adjust the account or date range or start by creating a transaction.');
    }

    public function getEmptyStateIcon(): string
    {
        return 'heroicon-o-x-mark';
    }

    public function getEmptyStateActions(): array
    {
        return [
            Action::make('createTransaction')
                ->label(__('Create transaction'))
                ->url(TransactionResource::getUrl()),
        ];
    }

    public function tableHasEmptyState(): bool
    {
        return empty($this->report?->getCategories());
    }
}
