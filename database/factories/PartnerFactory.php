<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'name' => $this->faker->name,
            'document' => $this->faker->unique()->numerify('############'),
            'email' => $this->faker->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'commission_percent' => 20,
            'active' => true,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
