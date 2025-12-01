<?php

namespace App\Observers;

use App\Models\FinancialRecord;
use App\Services\FinancialRecordAccountingService;

class FinancialRecordObserver
{
    public function __construct(private FinancialRecordAccountingService $accountingService)
    {
    }

    public function saved(FinancialRecord $record): void
    {
        $this->accountingService->syncTransaction($record);
    }

    public function deleting(FinancialRecord $record): void
    {
        $this->accountingService->deleteTransaction($record);
    }
}
