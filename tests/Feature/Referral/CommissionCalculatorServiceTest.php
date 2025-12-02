<?php

namespace Tests\Feature\Referral;

use App\Models\Company;
use App\Models\Referral\Referrer;
use App\Models\Referral\ReferrerRelation;
use App\Services\Referral\CommissionCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_direct_and_parent_commissions(): void
    {
        $company = Company::factory()->create();

        $parent = Referrer::factory()->create([
            'company_id' => $company->id,
            'default_commission_percentage' => 20,
        ]);

        $child = Referrer::factory()->create([
            'company_id' => $company->id,
            'default_commission_percentage' => 10,
        ]);

        ReferrerRelation::create([
            'parent_id' => $parent->id,
            'child_id' => $child->id,
            'commission_percentage' => 20,
        ]);

        $case = $child->referralCases()->create([
            'company_id' => $company->id,
            'description' => 'Novo caso teste',
            'case_value' => 1000,
            'status' => 'pending',
        ]);

        $service = new CommissionCalculatorService();
        $commissions = $service->calculateAndPersist($case);

        $this->assertCount(2, $commissions);
        $this->assertEquals(10, $commissions->firstWhere('referrer_id', $child->id)->commission_percentage);
        $this->assertEquals(100, $commissions->firstWhere('referrer_id', $child->id)->commission_value);
        $this->assertEquals(20, $commissions->firstWhere('referrer_id', $parent->id)->commission_percentage);
        $this->assertEquals(200, $commissions->firstWhere('referrer_id', $parent->id)->commission_value);
    }
}
