<?php

namespace App\Services\Referral;

use App\Models\FinancialRecord;
use App\Models\Referral\ReferralCase;
use App\Models\Referral\ReferralCommission;
use App\Models\Referral\Referrer;
use App\Models\Referral\ReferrerRelation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CommissionCalculatorService
{
    public function calculateAndPersist(ReferralCase $referralCase, ?Carbon $dueDate = null): Collection
    {
        $commissions = collect();
        $currentReferrer = $referralCase->referrer;
        $visited = [];
        $level = 0;
        $dueDate = $dueDate ?? $referralCase->expected_payment_date ?? now();

        while ($currentReferrer && ! in_array($currentReferrer->id, $visited, true)) {
            $visited[] = $currentReferrer->id;
            $percentage = $this->resolvePercentage($currentReferrer, $level, $referralCase);

            if ($percentage !== null) {
                $commissionValue = (float) $referralCase->case_value * ((float) $percentage / 100);

                $commission = ReferralCommission::updateOrCreate(
                    [
                        'referral_case_id' => $referralCase->id,
                        'referrer_id' => $currentReferrer->id,
                        'level' => $level,
                    ],
                    [
                        'commission_percentage' => $percentage,
                        'commission_value' => $commissionValue,
                        'status' => 'pending',
                        'due_date' => $dueDate,
                    ]
                );

                $commissions->push($commission);
            }

            $parentRelation = ReferrerRelation::where('child_id', $currentReferrer->id)
                ->where('active', true)
                ->first();

            $currentReferrer = $parentRelation?->parent;
            $level++;

            if ($level > 10) {
                break; // segurança contra loops inesperados
            }
        }

        return $commissions;
    }

    protected function resolvePercentage(Referrer $referrer, int $level, ReferralCase $referralCase): ?float
    {
        if ($level === 0) {
            return $referrer->default_commission_percentage;
        }

        $relation = ReferrerRelation::where('child_id', $referralCase->referrer_id)
            ->where('parent_id', $referrer->id)
            ->where('active', true)
            ->first();

        if (! $relation) {
            $relation = ReferrerRelation::where('child_id', $referrer->id)
                ->where('active', true)
                ->first();
        }

        return $relation?->commission_percentage ?? $referrer->default_commission_percentage;
    }

    public function attachFinancialRecord(ReferralCommission $commission, FinancialRecord $financialRecord): void
    {
        $commission->update(['financial_record_id' => $financialRecord->id]);
        $financialRecord->update([
            'referrer_id' => $commission->referrer_id,
            'referral_case_id' => $commission->referral_case_id,
            'referral_commission_id' => $commission->id,
        ]);
    }
}
