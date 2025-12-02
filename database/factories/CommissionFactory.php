<?php

namespace Database\Factories;

use App\Enums\CommissionStatus;
use App\Models\Commission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commission>
 */
class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'partner_id' => 1,
            'client_id' => 1,
            'legal_case_id' => null,
            'invoice_id' => 1,
            'bill_id' => null,
            'base_amount' => 1000,
            'commission_percent' => 20,
            'commission_amount' => 200,
            'status' => CommissionStatus::Pending,
            'due_date' => now()->addWeek()->toDateString(),
            'paid_at' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
