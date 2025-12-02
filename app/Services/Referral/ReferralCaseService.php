<?php

namespace App\Services\Referral;

use App\Models\FinancialRecord;
use App\Models\Referral\ReferralCase;
use App\Models\Referral\ReferralCommission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReferralCaseService
{
    public function __construct(
        protected CommissionCalculatorService $calculator,
    ) {
    }

    public function createCase(array $data, ?User $actor = null): ReferralCase
    {
        return DB::transaction(function () use ($data, $actor) {
            if ($actor && empty($data['company_id'])) {
                $data['company_id'] = $actor->current_company_id;
            }

            /** @var ReferralCase $case */
            $case = ReferralCase::create($data);

            $commissions = $this->calculator->calculateAndPersist(
                $case,
                isset($data['expected_payment_date']) ? Carbon::parse($data['expected_payment_date']) : null
            );

            $this->generateFinancialRecords($case, $commissions, $actor);

            return $case->fresh(['commissions']);
        });
    }

    protected function generateFinancialRecords(ReferralCase $case, $commissions, ?User $actor = null): void
    {
        if ($case->case_value > 0) {
            $this->createFinancialRecord([
                'company_id' => $case->company_id,
                'user_id' => $actor?->id ?? $case->referrer?->id,
                'type' => 'income',
                'amount' => $case->case_value,
                'currency' => 'BRL',
                'occurred_on' => $case->contract_date ?? now(),
                'description' => __('Honorários de indicação: :description', ['description' => $case->description]),
                'referral_case_id' => $case->id,
                'referrer_id' => $case->referrer_id,
                'transaction_id' => $this->resolveIncomeTransactionId($case),
            ]);
        }

        /** @var ReferralCommission $commission */
        foreach ($commissions as $commission) {
            $record = $this->createFinancialRecord([
                'company_id' => $case->company_id,
                'user_id' => $actor?->id ?? $case->referrer?->id,
                'type' => 'expense',
                'amount' => $commission->commission_value,
                'currency' => 'BRL',
                'occurred_on' => $commission->due_date ?? now(),
                'description' => __('Comissão de indicação para :name', ['name' => $commission->referrer->name]),
                'referral_case_id' => $case->id,
                'referrer_id' => $commission->referrer_id,
                'referral_commission_id' => $commission->id,
                'transaction_id' => $this->resolveCommissionTransactionId($commission),
            ]);

            if ($record) {
                $this->calculator->attachFinancialRecord($commission, $record);
            }
        }
    }

    protected function resolveIncomeTransactionId(ReferralCase $case): ?int
    {
        if ($case->invoice) {
            return $case->invoice->transactions()->latest('posted_at')->value('id');
        }

        return null;
    }

    protected function resolveCommissionTransactionId(ReferralCommission $commission): ?int
    {
        if ($commission->transaction_id) {
            return $commission->transaction_id;
        }

        if ($commission->bill) {
            return $commission->bill->transactions()->latest('posted_at')->value('id');
        }

        return null;
    }

    protected function createFinancialRecord(array $data): ?FinancialRecord
    {
        if (empty($data['user_id'])) {
            return null;
        }

        return FinancialRecord::create($data);
    }
}
