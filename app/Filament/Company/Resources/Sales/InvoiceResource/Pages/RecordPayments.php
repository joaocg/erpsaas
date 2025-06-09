<?php

namespace App\Filament\Company\Resources\Sales\InvoiceResource\Pages;

use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Accounting\PaymentMethod;
use App\Filament\Company\Resources\Sales\InvoiceResource;
use App\Filament\Tables\Columns\CustomTextInputColumn;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Models\Common\Client;
use App\Models\Setting\Currency;
use App\Utilities\Currency\CurrencyAccessor;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * @property Form $form
 */
class RecordPayments extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static string $view = 'filament.company.resources.sales.invoice-resource.pages.record-payments';

    public array $paymentAmounts = [];

    public ?array $data = [];

    // New property for allocation amount
    public ?int $allocationAmount = null;

    #[Url(as: 'invoice_id')]
    public ?int $invoiceId = null;

    public function getBreadcrumb(): ?string
    {
        return 'Record Payments';
    }

    public function getTitle(): string | Htmlable
    {
        return 'Record Payments';
    }

    public function getMaxContentWidth(): MaxWidth | string | null
    {
        return 'max-w-8xl';
    }

    public function mount(): void
    {
        parent::mount();

        $preservedClientId = $this->tableFilters['client_id']['value'] ?? null;
        $preservedCurrencyCode = $this->tableFilters['currency_code']['value'] ?? CurrencyAccessor::getDefaultCurrency();

        $this->tableFilters = [
            'client_id' => $preservedClientId ? ['value' => $preservedClientId] : [],
            'currency_code' => ['value' => $preservedCurrencyCode],
        ];

        // Auto-fill payment amount if invoice_id is provided
        if ($invoiceId = $this->invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if ($invoice && $invoice->client_id == $preservedClientId) {
                $this->paymentAmounts[$invoiceId] = $invoice->amount_due;
            }
        }

        $this->form->fill();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('processPayments')
                ->color('primary')
                ->action(function () {
                    $data = $this->data;
                    $tableRecords = $this->getTableRecords();
                    $paidCount = 0;
                    $totalPaid = 0;

                    /** @var Invoice $invoice */
                    foreach ($tableRecords as $invoice) {
                        if (! $invoice->canRecordPayment()) {
                            continue;
                        }

                        // Get the payment amount from our component state
                        $paymentAmount = $this->getPaymentAmount($invoice);

                        if ($paymentAmount <= 0) {
                            continue;
                        }

                        $paymentData = [
                            'posted_at' => $data['posted_at'],
                            'payment_method' => $data['payment_method'],
                            'bank_account_id' => $data['bank_account_id'],
                            'amount' => $paymentAmount,
                        ];

                        $invoice->recordPayment($paymentData);
                        $paidCount++;
                        $totalPaid += $paymentAmount;
                    }

                    $currencyCode = $this->getTableFilterState('currency_code')['value'];
                    $totalFormatted = CurrencyConverter::formatCentsToMoney($totalPaid, $currencyCode, true);

                    Notification::make()
                        ->title('Payments recorded successfully')
                        ->body("Recorded {$paidCount} " . Str::plural('payment', $paidCount) . " for a total of {$totalFormatted}")
                        ->success()
                        ->send();

                    $this->reset('paymentAmounts', 'allocationAmount');

                    $this->resetTable();
                }),
        ];
    }

    protected function allocateOldestFirst(Collection $invoices, int $amountInCents): void
    {
        $remainingAmount = $amountInCents;

        $sortedInvoices = $invoices->sortBy('due_date');

        foreach ($sortedInvoices as $invoice) {
            if ($remainingAmount <= 0) {
                break;
            }

            $amountDue = $invoice->amount_due;
            $allocation = min($remainingAmount, $amountDue);

            $this->paymentAmounts[$invoice->id] = $allocation;
            $remainingAmount -= $allocation;
        }
    }

    protected function hasSelectedClient(): bool
    {
        return ! empty($this->getTableFilterState('client_id')['value']);
    }

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->live()
            ->schema([
                Forms\Components\Grid::make(2) // Changed from 3 to 4
                    ->schema([
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Account')
                            ->options(static function () {
                                return Transaction::getBankAccountOptionsFlat();
                            })
                            ->default(fn () => BankAccount::where('enabled', true)->first()?->id)
                            ->selectablePlaceholder(false)
                            ->searchable()
                            ->softRequired(),
                        Forms\Components\DatePicker::make('posted_at')
                            ->label('Date')
                            ->default(now())
                            ->softRequired(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment method')
                            ->selectablePlaceholder(false)
                            ->options(PaymentMethod::class)
                            ->default(PaymentMethod::BankPayment)
                            ->softRequired(),
                        // Allocation amount field with suffix action
                        Forms\Components\TextInput::make('allocation_amount')
                            ->label('Allocate Payment Amount')
                            ->live()
                            ->default(array_sum($this->paymentAmounts))
                            ->money($this->getTableFilterState('currency_code')['value'])
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('allocate')
                                    ->icon('heroicon-m-calculator')
                                    ->action(function ($state) {
                                        $this->allocationAmount = CurrencyConverter::convertToCents($state, 'USD');
                                        if ($this->allocationAmount && $this->hasSelectedClient()) {
                                            $this->allocateOldestFirst($this->getTableRecords(), $this->allocationAmount);

                                            $amountFormatted = CurrencyConverter::formatCentsToMoney($this->allocationAmount, 'USD', true);

                                            Notification::make()
                                                ->title('Payment allocated')
                                                ->body("Allocated {$amountFormatted} across invoices")
                                                ->success()
                                                ->send();
                                        }
                                    })
                            ),
                    ]),
            ])->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->with(['client'])
                    ->unpaid()
            )
            ->recordClasses(['is-spreadsheet'])
            ->defaultSort('due_date')
            ->paginated(false)
            ->columns([
                TextColumn::make('client.name')
                    ->label('Client')
                    ->sortable(),
                TextColumn::make('invoice_number')
                    ->label('Invoice number')
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due date')
                    ->defaultDateFormat()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount_due')
                    ->label('Amount due')
                    ->currency(static fn (Invoice $record) => $record->currency_code)
                    ->alignEnd()
                    ->sortable()
                    ->summarize([
                        Summarizer::make()
                            ->using(function (Builder $query) {
                                $totalAmountDue = $query->sum('amount_due');
                                $bankAccountCurrency = $this->getSelectedBankAccount()->account->currency_code;
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? $bankAccountCurrency;

                                if ($activeCurrency !== $bankAccountCurrency) {
                                    $totalAmountDue = CurrencyConverter::convertBalance($totalAmountDue, $activeCurrency, $bankAccountCurrency);
                                }

                                return CurrencyConverter::formatCentsToMoney($totalAmountDue, $bankAccountCurrency, true);
                            }),
                        Summarizer::make()
                            ->using(function (Builder $query) {
                                $totalAmountDue = $query->sum('amount_due');
                                $currencyCode = $this->getTableFilterState('currency_code')['value'];

                                return CurrencyConverter::formatCentsToMoney($totalAmountDue, $currencyCode, true);
                            })
                            ->visible(function () {
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? null;
                                $bankAccountCurrency = $this->getSelectedBankAccount()->account->currency_code;

                                return $activeCurrency && $activeCurrency !== $bankAccountCurrency;
                            }),
                    ]),
                CustomTextInputColumn::make('payment_amount')
                    ->label('Payment amount')
                    ->alignEnd()
                    ->navigable()
                    ->mask(RawJs::make('$money($input)'))
                    ->updateStateUsing(function (Invoice $record, $state) {
                        if (! CurrencyConverter::isValidAmount($state, 'USD')) {
                            $this->paymentAmounts[$record->id] = 0;

                            return '0.00';
                        }

                        $paymentCents = CurrencyConverter::convertToCents($state, 'USD');

                        if ($paymentCents > $record->amount_due) {
                            $paymentCents = $record->amount_due;
                        }

                        $this->paymentAmounts[$record->id] = $paymentCents;

                        return $state;
                    })
                    ->getStateUsing(function (Invoice $record) {
                        $paymentAmount = $this->paymentAmounts[$record->id] ?? 0;

                        return CurrencyConverter::convertCentsToFormatSimple($paymentAmount, 'USD');
                    })
                    ->summarize([
                        Summarizer::make()
                            ->using(function () {
                                $total = array_sum($this->paymentAmounts);
                                $bankAccountCurrency = $this->getSelectedBankAccount()->account->currency_code;
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? $bankAccountCurrency;

                                if ($activeCurrency !== $bankAccountCurrency) {
                                    $total = CurrencyConverter::convertBalance($total, $activeCurrency, $bankAccountCurrency);
                                }

                                return CurrencyConverter::formatCentsToMoney($total, $bankAccountCurrency, true);
                            }),
                        Summarizer::make()
                            ->using(fn () => $this->totalPaymentAmount)
                            ->visible(function () {
                                $activeCurrency = $this->getTableFilterState('currency_code')['value'] ?? null;
                                $bankAccountCurrency = $this->getSelectedBankAccount()->account->currency_code;

                                return $activeCurrency && $activeCurrency !== $bankAccountCurrency;
                            }),
                    ]),
                // New allocation status column
                TextColumn::make('allocation_status')
                    ->label('Status')
                    ->getStateUsing(function (Invoice $record) {
                        $paymentAmount = $this->paymentAmounts[$record->id] ?? 0;

                        if ($paymentAmount <= 0) {
                            return 'No payment';
                        }

                        if ($paymentAmount >= $record->amount_due) {
                            return 'Full payment';
                        }

                        return 'Partial payment';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Full payment' => 'success',
                        'Partial payment' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('setFullAmounts')
                    ->label('Set full amounts')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records->each(function (Invoice $invoice) {
                            $this->paymentAmounts[$invoice->id] = $invoice->amount_due;
                        });
                    }),
                Tables\Actions\BulkAction::make('clearAmounts')
                    ->label('Clear amounts')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records->each(function (Invoice $invoice) {
                            $this->paymentAmounts[$invoice->id] = 0;
                        });
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency_code')
                    ->label('Currency')
                    ->selectablePlaceholder(false)
                    ->default(CurrencyAccessor::getDefaultCurrency())
                    ->options(Currency::query()->pluck('name', 'code')->toArray())
                    ->searchable()
                    ->resetState([
                        'value' => CurrencyAccessor::getDefaultCurrency(),
                    ])
                    ->indicateUsing(function (Tables\Filters\SelectFilter $filter, array $state) {
                        if (blank($state['value'] ?? null)) {
                            return [];
                        }

                        $label = collect($filter->getOptions())
                            ->mapWithKeys(fn (string | array $label, string $value): array => is_array($label) ? $label : [$value => $label])
                            ->get($state['value']);

                        if (blank($label)) {
                            return [];
                        }

                        $indicator = $filter->getLabel();

                        return Tables\Filters\Indicator::make("{$indicator}: {$label}")->removable(false);
                    }),
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->selectablePlaceholder(false)
                    ->options(fn () => Client::query()->pluck('name', 'id')->toArray())
                    ->searchable()
                    ->query(function (EloquentBuilder $query, array $data) {
                        if (blank($data['value'] ?? null)) {
                            return $query->whereRaw('1 = 0'); // No results if no client is selected
                        }

                        return $query->where('client_id', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(InvoiceStatus::getUnpaidOptions()),
            ]);
    }

    protected function getPaymentAmount(Invoice $record): int
    {
        return $this->paymentAmounts[$record->id] ?? 0;
    }

    #[Computed]
    public function totalPaymentAmount(): string
    {
        $total = array_sum($this->paymentAmounts);

        $currencyCode = $this->getTableFilterState('currency_code')['value'];

        return CurrencyConverter::formatCentsToMoney($total, $currencyCode, true);
    }

    #[Computed]
    public function allocationVariance(): string
    {
        if (! $this->allocationAmount) {
            return '$0.00';
        }

        $totalAllocated = array_sum($this->paymentAmounts);
        $variance = $this->allocationAmount - $totalAllocated;

        $currencyCode = $this->getTableFilterState('currency_code')['value'];

        return CurrencyConverter::formatCentsToMoney($variance, $currencyCode, true);
    }

    public function getSelectedBankAccount(): BankAccount
    {
        $bankAccountId = $this->data['bank_account_id'];

        $bankAccount = BankAccount::find($bankAccountId);

        return $bankAccount ?: BankAccount::where('enabled', true)->first();
    }

    protected function handleTableFilterUpdates(): void
    {
        parent::handleTableFilterUpdates();

        $visibleInvoiceIds = $this->getTableRecords()->pluck('id')->toArray();
        $visibleInvoiceKeys = array_flip($visibleInvoiceIds);

        $this->paymentAmounts = array_intersect_key($this->paymentAmounts, $visibleInvoiceKeys);

        // Reset allocation when client changes
        $this->allocationAmount = null;
    }
}
