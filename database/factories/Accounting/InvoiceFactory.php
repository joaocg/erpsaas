<?php

namespace Database\Factories\Accounting;

use App\Enums\Accounting\AdjustmentComputation;
use App\Enums\Accounting\DocumentDiscountMethod;
use App\Enums\Accounting\InvoiceStatus;
use App\Enums\Accounting\PaymentMethod;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\Invoice;
use App\Models\Banking\BankAccount;
use App\Models\Common\Client;
use App\Models\Company;
use App\Models\Setting\DocumentDefault;
use App\Utilities\RateCalculator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Random\RandomException;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceDate = $this->faker->dateTimeBetween('-2 months');

        return [
            'company_id' => 1,
            'client_id' => function (array $attributes) {
                return Client::where('company_id', $attributes['company_id'])->inRandomOrder()->value('id')
                    ?? Client::factory()->state([
                        'company_id' => $attributes['company_id'],
                    ]);
            },
            'header' => 'Invoice',
            'subheader' => 'Invoice',
            'invoice_number' => $this->faker->unique()->numerify('INV-####'),
            'order_number' => $this->faker->unique()->numerify('ORD-####'),
            'date' => $invoiceDate,
            'due_date' => $this->faker->dateTimeInInterval($invoiceDate, '+3 months'),
            'status' => InvoiceStatus::Draft,
            'discount_method' => $this->faker->randomElement(DocumentDiscountMethod::class),
            'discount_computation' => AdjustmentComputation::Percentage,
            'discount_rate' => function (array $attributes) {
                $discountMethod = DocumentDiscountMethod::parse($attributes['discount_method']);

                if ($discountMethod?->isPerDocument()) {
                    return $this->faker->numberBetween(50000, 200000); // 5% - 20%
                }

                return 0;
            },
            'currency_code' => function (array $attributes) {
                $client = Client::find($attributes['client_id']);

                return $client->currency_code ??
                    Company::find($attributes['company_id'])->default->currency_code ??
                    'USD';
            },
            'terms' => $this->faker->sentence,
            'footer' => $this->faker->sentence,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }

    public function withLineItems(int $count = 3): static
    {
        return $this->afterCreating(function (Invoice $invoice) use ($count) {
            DocumentLineItem::factory()
                ->count($count)
                ->forInvoice($invoice)
                ->create();

            $this->recalculateTotals($invoice);
        });
    }

    public function approved(): static
    {
        return $this->afterCreating(function (Invoice $invoice) {
            $this->performApproval($invoice);
        });
    }

    public function sent(): static
    {
        return $this->afterCreating(function (Invoice $invoice) {
            $this->performSent($invoice);
        });
    }

    public function partial(int $maxPayments = 4): static
    {
        return $this->afterCreating(function (Invoice $invoice) use ($maxPayments) {
            $this->performSent($invoice);
            $this->performPayments($invoice, $maxPayments, InvoiceStatus::Partial);
        });
    }

    public function paid(int $maxPayments = 4): static
    {
        return $this->afterCreating(function (Invoice $invoice) use ($maxPayments) {
            $this->performSent($invoice);
            $this->performPayments($invoice, $maxPayments, InvoiceStatus::Paid);
        });
    }

    public function overpaid(int $maxPayments = 4): static
    {
        return $this->afterCreating(function (Invoice $invoice) use ($maxPayments) {
            $this->performSent($invoice);
            $this->performPayments($invoice, $maxPayments, InvoiceStatus::Overpaid);
        });
    }

    public function overdue(): static
    {
        return $this
            ->state([
                'due_date' => now()->subDays($this->faker->numberBetween(1, 30)),
            ])
            ->afterCreating(function (Invoice $invoice) {
                $this->performApproval($invoice);
            });
    }

    protected function performApproval(Invoice $invoice): void
    {
        if (! $invoice->hasLineItems()) {
            throw new \InvalidArgumentException('Cannot approve invoice without line items. Use withLineItems() first.');
        }

        if (! $invoice->canBeApproved()) {
            throw new \InvalidArgumentException('Invoice cannot be approved. Current status: ' . $invoice->status->value);
        }

        $approvedAt = Carbon::parse($invoice->date)
            ->addHours($this->faker->numberBetween(1, 24));

        $invoice->approveDraft($approvedAt);
    }

    protected function performSent(Invoice $invoice): void
    {
        if (! $invoice->wasApproved()) {
            $this->performApproval($invoice);
        }

        $sentAt = Carbon::parse($invoice->approved_at)
            ->addHours($this->faker->numberBetween(1, 24));

        $invoice->markAsSent($sentAt);
    }

    /**
     * @throws RandomException
     */
    protected function performPayments(Invoice $invoice, int $maxPayments, InvoiceStatus $invoiceStatus): void
    {
        $invoice->refresh();

        $amountDue = $invoice->getRawOriginal('amount_due');

        $totalAmountDue = match ($invoiceStatus) {
            InvoiceStatus::Overpaid => $amountDue + random_int(1000, 10000),
            InvoiceStatus::Partial => (int) floor($amountDue * 0.5),
            default => $amountDue,
        };

        if ($totalAmountDue <= 0 || empty($totalAmountDue)) {
            return;
        }

        $paymentCount = $this->faker->numberBetween(1, $maxPayments);
        $paymentAmount = (int) floor($totalAmountDue / $paymentCount);
        $remainingAmount = $totalAmountDue;

        $paymentDate = Carbon::parse($invoice->approved_at);
        $paymentDates = [];

        for ($i = 0; $i < $paymentCount; $i++) {
            $amount = $i === $paymentCount - 1 ? $remainingAmount : $paymentAmount;

            if ($amount <= 0) {
                break;
            }

            $postedAt = $paymentDate->copy()->addDays($this->faker->numberBetween(1, 30));
            $paymentDates[] = $postedAt;

            $data = [
                'posted_at' => $postedAt,
                'amount' => $amount,
                'payment_method' => $this->faker->randomElement(PaymentMethod::class),
                'bank_account_id' => BankAccount::where('company_id', $invoice->company_id)->inRandomOrder()->value('id'),
                'notes' => $this->faker->sentence,
            ];

            $invoice->recordPayment($data);
            $remainingAmount -= $amount;
        }

        if ($invoiceStatus === InvoiceStatus::Paid && ! empty($paymentDates)) {
            $latestPaymentDate = max($paymentDates);
            $invoice->updateQuietly([
                'status' => $invoiceStatus,
                'paid_at' => $latestPaymentDate,
            ]);
        }
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Invoice $invoice) {
            $number = DocumentDefault::getBaseNumber() + $invoice->id;

            $invoice->updateQuietly([
                'invoice_number' => "INV-{$number}",
                'order_number' => "ORD-{$number}",
            ]);

            if ($invoice->wasApproved() && $invoice->shouldBeOverdue()) {
                $invoice->updateQuietly([
                    'status' => InvoiceStatus::Overdue,
                ]);
            }
        });
    }

    protected function recalculateTotals(Invoice $invoice): void
    {
        $invoice->refresh();

        if (! $invoice->hasLineItems()) {
            return;
        }

        $subtotalCents = $invoice->lineItems()->sum('subtotal');
        $taxTotalCents = $invoice->lineItems()->sum('tax_total');

        $discountTotalCents = 0;

        if ($invoice->discount_method?->isPerLineItem()) {
            $discountTotalCents = $invoice->lineItems()->sum('discount_total');
        } elseif ($invoice->discount_method?->isPerDocument() && $invoice->discount_rate) {
            if ($invoice->discount_computation?->isPercentage()) {
                $scaledRate = RateCalculator::parseLocalizedRate($invoice->discount_rate);
                $discountTotalCents = RateCalculator::calculatePercentage($subtotalCents, $scaledRate);
            } else {
                $discountTotalCents = $invoice->getRawOriginal('discount_rate');
            }
        }

        $grandTotalCents = $subtotalCents + $taxTotalCents - $discountTotalCents;

        $invoice->update([
            'subtotal' => $subtotalCents,
            'tax_total' => $taxTotalCents,
            'discount_total' => $discountTotalCents,
            'total' => $grandTotalCents,
        ]);
    }
}
