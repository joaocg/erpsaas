<?php

namespace App\Observers;

use App\Models\PartnerAdvance;

class PartnerAdvanceObserver
{
    public function created(PartnerAdvance $partnerAdvance): void
    {
        $this->applyDelta($partnerAdvance, $partnerAdvance->amount);
    }

    public function deleted(PartnerAdvance $partnerAdvance): void
    {
        $this->applyDelta($partnerAdvance, -$partnerAdvance->amount);
    }

    protected function applyDelta(PartnerAdvance $advance, float $delta): void
    {
        $partner = $advance->partner;

        if (! $partner) {
            return;
        }

        $partner->updateQuietly([
            'current_balance' => ($partner->current_balance ?? 0) + $delta,
        ]);
    }
}
