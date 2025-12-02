<?php

namespace App\Services;

use App\Enums\Accounting\BillStatus;
use App\Enums\CommissionStatus;
use App\Models\Accounting\Bill;
use App\Models\Commission;
use App\Models\Accounting\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CommissionService
{
    public function createCommissionForInvoice(Invoice $invoice): ?Commission
    {
        if (! $invoice->partner_id || ! $invoice->wasApproved()) {
            return null;
        }

        $percent = $invoice->commission_percent ?? $invoice->partner?->commission_percent ?? 0;

        if (! $percent || $percent <= 0) {
            return null;
        }

        return Commission::query()->updateOrCreate(
            ['invoice_id' => $invoice->id],
            [
                'company_id' => $invoice->company_id,
                'partner_id' => $invoice->partner_id,
                'client_id' => $invoice->client_id,
                'legal_case_id' => null,
                'bill_id' => null,
                'base_amount' => $invoice->total,
                'commission_percent' => $percent,
                'commission_amount' => $this->calculateCommissionAmount($invoice->total, $percent),
                'status' => CommissionStatus::Pending,
                'due_date' => $invoice->due_date,
                'created_by' => $invoice->created_by,
                'updated_by' => $invoice->updated_by,
            ]
        );
    }

    public function accrueForPaidInvoice(Invoice $invoice, ?Carbon $paidAt = null): ?Commission
    {
        $commission = $invoice->commissions()->latest()->first();

        if (! $commission) {
            $commission = $this->createCommissionForInvoice($invoice);
        }

        if (! $commission) {
            return null;
        }

        if ($commission->status === CommissionStatus::Paid) {
            return $commission;
        }

        return DB::transaction(function () use ($commission, $invoice, $paidAt) {
            if (! $commission->bill_id) {
                $bill = $this->createBillForCommission($commission, $invoice, $paidAt);
                $commission->bill()->associate($bill);
            }

            if ($commission->status !== CommissionStatus::Accrued) {
                $commission->status = CommissionStatus::Accrued;
            }

            if ($paidAt && $commission->status === CommissionStatus::Paid) {
                $commission->paid_at = $paidAt;
            }

            $commission->save();

            return $commission;
        });
    }

    public function markBillAsPaid(Bill $bill, ?Carbon $paidAt = null): void
    {
        $commissions = Commission::query()->where('bill_id', $bill->id)->get();

        foreach ($commissions as $commission) {
            $commission->update([
                'status' => CommissionStatus::Paid,
                'paid_at' => $paidAt ?? $bill->paid_at,
            ]);
        }
    }

    protected function createBillForCommission(Commission $commission, Invoice $invoice, ?Carbon $paidAt = null): Bill
    {
        $bill = Bill::create([
            'company_id' => $invoice->company_id,
            'vendor_id' => null,
            'bill_number' => Bill::getNextDocumentNumber($invoice->company),
            'date' => $paidAt?->toDateString() ?? company_today(),
            'due_date' => $commission->due_date,
            'paid_at' => null,
            'status' => BillStatus::Open,
            'currency_code' => $invoice->currency_code,
            'discount_method' => $invoice->discount_method,
            'discount_computation' => $invoice->discount_computation,
            'discount_rate' => $invoice->discount_rate,
            'subtotal' => $commission->commission_amount,
            'tax_total' => 0,
            'discount_total' => 0,
            'total' => $commission->commission_amount,
            'amount_paid' => 0,
            'notes' => __('Commission payable for invoice :invoice', ['invoice' => $invoice->invoice_number]),
            'created_by' => $invoice->created_by,
            'updated_by' => $invoice->updated_by,
        ]);

        return $bill;
    }

    protected function calculateCommissionAmount(mixed $baseAmount, float $percent): float
    {
        return round(((float) $baseAmount) * $percent / 100, 2);
    }
}
