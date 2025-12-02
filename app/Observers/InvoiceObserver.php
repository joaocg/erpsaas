<?php

namespace App\Observers;

use App\Enums\Accounting\InvoiceStatus;
use App\Models\Accounting\DocumentLineItem;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Transaction;
use App\Services\CommissionService;
use Illuminate\Support\Facades\DB;

class InvoiceObserver
{
    public function saving(Invoice $invoice): void
    {
        if (! $invoice->wasApproved()) {
            return;
        }

        if ($invoice->isDirty('due_date') && $invoice->status === InvoiceStatus::Overdue && ! $invoice->shouldBeOverdue() && ! $invoice->hasPayments()) {
            $invoice->status = $invoice->hasBeenSent() ? InvoiceStatus::Sent : InvoiceStatus::Unsent;

            return;
        }

        if ($invoice->shouldBeOverdue()) {
            $invoice->status = InvoiceStatus::Overdue;
        }
    }

    public function saved(Invoice $invoice): void
    {
        if ($invoice->partner_id && $invoice->wasApproved()) {
            app(CommissionService::class)->createCommissionForInvoice($invoice);
        }

        if (in_array($invoice->status, [InvoiceStatus::Paid, InvoiceStatus::Overpaid], true) && $invoice->wasChanged('status')) {
            app(CommissionService::class)->accrueForPaidInvoice($invoice, $invoice->paid_at);
        }
    }

    public function deleted(Invoice $invoice): void
    {
        DB::transaction(function () use ($invoice) {
            $invoice->lineItems()->each(function (DocumentLineItem $lineItem) {
                $lineItem->delete();
            });

            $invoice->transactions()->each(function (Transaction $transaction) {
                $transaction->delete();
            });
        });
    }
}
