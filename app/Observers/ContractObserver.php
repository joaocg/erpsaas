<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\FinancialMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContractObserver
{
    public function created(Contract $contract): void
    {
        $this->createFinancialMovements($contract);
    }

    protected function createFinancialMovements(Contract $contract): void
    {
        $startDate = $contract->start_date ? Carbon::parse($contract->start_date) : now();
        $companyId = $contract->company_id;

        $entryAmount = $contract->entry_amount ?? 0;
        $installmentCount = max(1, (int) $contract->installment_count);
        $remaining = $contract->total_amount - $entryAmount;
        $installmentAmount = $installmentCount > 0 ? round($remaining / $installmentCount, 2) : 0;

        $movements = Collection::make();

        if ($entryAmount > 0) {
            $movements->push([
                'company_id' => $companyId,
                'contract_id' => $contract->id,
                'type' => 'entry',
                'amount' => $entryAmount,
                'due_date' => $startDate,
            ]);
        }

        for ($i = 1; $i <= $installmentCount; $i++) {
            $movements->push([
                'company_id' => $companyId,
                'contract_id' => $contract->id,
                'type' => 'installment',
                'amount' => $installmentAmount,
                'due_date' => $startDate->copy()->addMonthsNoOverflow($i),
            ]);
        }

        $this->rebalanceAmounts($movements, $contract->total_amount);

        FinancialMovement::insert($movements->map(fn ($data) => [
            ...$data,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray());
    }

    protected function rebalanceAmounts(Collection $movements, float $targetTotal): void
    {
        $currentTotal = $movements->sum('amount');
        $difference = round($targetTotal - $currentTotal, 2);

        if (abs($difference) < 0.01) {
            return;
        }

        if ($movements->isNotEmpty()) {
            $movements[0]['amount'] = round($movements[0]['amount'] + $difference, 2);
        }
    }
}
