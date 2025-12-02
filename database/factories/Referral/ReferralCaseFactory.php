<?php

namespace Database\Factories\Referral;

use App\Models\Common\Client;
use App\Models\Company;
use App\Models\Referral\ReferralCase;
use App\Models\Referral\Referrer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReferralCase>
 */
class ReferralCaseFactory extends Factory
{
    protected $model = ReferralCase::class;

    public function definition(): array
    {
        $company = Company::factory()->create();

        return [
            'company_id' => $company->id,
            'referrer_id' => Referrer::factory()->for($company),
            'client_id' => Client::factory()->for($company),
            'description' => $this->faker->sentence(),
            'case_value' => 1000,
            'status' => 'pending',
        ];
    }
}
