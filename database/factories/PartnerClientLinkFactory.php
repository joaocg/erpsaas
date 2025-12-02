<?php

namespace Database\Factories;

use App\Models\PartnerClientLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerClientLink>
 */
class PartnerClientLinkFactory extends Factory
{
    protected $model = PartnerClientLink::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'partner_id' => 1,
            'client_id' => 1,
            'linked_at' => now()->toDateString(),
            'notes' => $this->faker->sentence,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
