<?php

namespace Database\Factories\Referral;

use App\Models\Company;
use App\Models\Referral\Referrer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referrer>
 */
class ReferrerFactory extends Factory
{
    protected $model = Referrer::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->name(),
            'type' => 'referrer',
            'default_commission_percentage' => 10,
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'whatsapp' => $this->faker->phoneNumber(),
        ];
    }
}
