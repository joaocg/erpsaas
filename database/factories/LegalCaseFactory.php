<?php

namespace Database\Factories;

use App\Models\LegalCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LegalCase>
 */
class LegalCaseFactory extends Factory
{
    protected $model = LegalCase::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'client_id' => 1,
            'partner_id' => 1,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => 'open',
            'expected_receivable_date' => now()->addMonth()->toDateString(),
            'expected_receivable_amount' => $this->faker->randomFloat(2, 1000, 5000),
            'fee_percent' => 20,
            'fee_amount' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
