<?php

namespace App\Filament\Company\Resources\Purchases\BillResource\Pages;

use App\Enums\Accounting\BillStatus;
use App\Enums\Accounting\PaymentMethod;
use App\Filament\Company\Resources\Purchases\BillResource;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Transaction;
use App\Models\Banking\BankAccount;
use App\Utilities\Currency\CurrencyConverter;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

class PayBills extends ListRecords
{
    protected static string $resource = BillResource::class;

    protected static ?string $title = 'Pay Bills';

    protected static ?string $navigationLabel = 'Pay Bills';

    protected static string $view = 'filament.company.resources.purchases.bill-resource.pages.pay-bills';

    public array $paymentAmounts = [];

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Pay Bills';
    }

    public function getBreadcrumb(): string
    {
        return 'Pay Bills';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'bank_account_id' => BankAccount::where('enabled', true)->first()?->id,
            'payment_date' => now(),
            'payment_method' => PaymentMethod::Check->value,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('paySelected')
                ->label('Pay Selected Bills')
                ->icon('heroicon-o-credit-card')
                ->color('primary')
                ->action(function () {
                    $data = $this->data;
                    $selectedRecords = $this->getTableRecords();
                    $paidCount = 0;
                    $totalPaid = 0;

                    foreach ($selectedRecords as $bill) {
                        if (! $bill->canRecordPayment()) {
                            continue;
                        }

                        // Get the payment amount from our component state
                        $paymentAmount = $this->getPaymentAmount($bill);

                        if ($paymentAmount <= 0) {
                            continue;
                        }

                        $paymentData = [
                            'posted_at' => $data['payment_date'],
                            'payment_method' => $data['payment_method'],
                            'bank_account_id' => $data['bank_account_id'],
                            'amount' => $paymentAmount,
                        ];

                        $bill->recordPayment($paymentData);
                        $paidCount++;
                        $totalPaid += $paymentAmount;
                    }

                    $totalFormatted = CurrencyConverter::formatCentsToMoney($totalPaid);

                    Notification::make()
                        ->title('Bills paid successfully')
                        ->body("Paid {$paidCount} bill(s) for a total of {$totalFormatted}")
                        ->success()
                        ->send();

                    // Clear payment amounts after successful payment
                    foreach ($selectedRecords as $bill) {
                        $this->paymentAmounts[$bill->id] = 0;
                    }

                    $this->resetTable();
                }),
        ];
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
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Bank Account')
                            ->options(function () {
                                return Transaction::getBankAccountOptionsFlat();
                            })
                            ->selectablePlaceholder(false)
                            ->searchable()
                            ->softRequired(),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->default(now())
                            ->softRequired(),
                        Forms\Components\Select::make('payment_method')
                            ->label('Payment Method')
                            ->selectablePlaceholder(false)
                            ->options(PaymentMethod::class)
                            ->default(PaymentMethod::Check)
                            ->softRequired()
                            ->live(),
                    ]),
            ])->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Bill::query()
                    ->with(['vendor'])
                    ->whereIn('status', [
                        BillStatus::Open,
                        BillStatus::Partial,
                        BillStatus::Overdue,
                    ])
            )
            ->selectable()
            ->columns([
                TextColumn::make('vendor.name')
                    ->label('Vendor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bill_number')
                    ->label('Bill #')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('M j, Y')
                    ->sortable(),
                TextColumn::make('amount_due')
                    ->label('Amount Due')
                    ->currencyWithConversion(fn (Bill $record) => $record->currency_code)
                    ->alignEnd()
                    ->sortable(),
                TextInputColumn::make('payment_amount')
                    ->label('Payment Amount')
                    ->alignEnd()
                    ->mask(RawJs::make('$money($input)'))
                    ->updateStateUsing(function (Bill $record, $state) {
                        if (empty($state) || $state === '0.00') {
                            $this->paymentAmounts[$record->id] = 0;

                            return '0.00';
                        }

                        $paymentCents = CurrencyConverter::convertToCents($state, $record->currency_code);

                        // Validate payment doesn't exceed amount due
                        if ($paymentCents > $record->amount_due) {
                            Notification::make()
                                ->title('Invalid payment amount')
                                ->body('Payment cannot exceed amount due')
                                ->warning()
                                ->send();

                            $maxAmount = CurrencyConverter::convertCentsToFormatSimple($record->amount_due, $record->currency_code);
                            $this->paymentAmounts[$record->id] = $record->amount_due;

                            return $maxAmount;
                        }

                        $this->paymentAmounts[$record->id] = $paymentCents;

                        return $state;
                    })
                    ->getStateUsing(function (Bill $record) {
                        $paymentAmount = $this->paymentAmounts[$record->id] ?? 0;

                        return CurrencyConverter::convertCentsToFormatSimple($paymentAmount, $record->currency_code);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('setFullAmount')
                    ->label('Pay Full')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->action(function (Bill $record) {
                        $this->paymentAmounts[$record->id] = $record->amount_due;
                    }),
                Tables\Actions\Action::make('clearAmount')
                    ->label('Clear')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->action(function (Bill $record) {
                        $this->paymentAmounts[$record->id] = 0;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('setFullAmounts')
                    ->label('Set Full Amounts')
                    ->icon('heroicon-o-banknotes')
                    ->color('primary')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records->each(function (Bill $bill) {
                            $this->paymentAmounts[$bill->id] = $bill->amount_due;
                        });
                    }),
                Tables\Actions\BulkAction::make('clearAmounts')
                    ->label('Clear Amounts')
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $records->each(function (Bill $bill) {
                            $this->paymentAmounts[$bill->id] = 0;
                        });
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('vendor')
                    ->relationship('vendor', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options([
                        BillStatus::Open->value => 'Open',
                        BillStatus::Partial->value => 'Partial',
                        BillStatus::Overdue->value => 'Overdue',
                    ])
                    ->default([BillStatus::Open->value, BillStatus::Overdue->value]),
            ])
            ->defaultSort('due_date')
            ->striped()
            ->paginated(false);
    }

    protected function getPaymentAmount(Bill $record): int
    {
        return $this->paymentAmounts[$record->id] ?? 0;
    }

    #[Computed]
    public function totalSelectedPaymentAmount(): string
    {
        $selectedIds = array_keys($this->getSelectedTableRecords()->toArray());
        $total = collect($selectedIds)
            ->map(fn ($id) => $this->paymentAmounts[$id] ?? 0)
            ->sum();

        return CurrencyConverter::formatCentsToMoney($total);
    }
}
