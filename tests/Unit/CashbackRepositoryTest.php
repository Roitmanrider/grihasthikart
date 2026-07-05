<?php

namespace Tests\Unit;

use App\Domains\Cashback\Repositories\CashbackRepository;
use App\Models\CashbackLedger;
use App\Models\CashbackRule;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashbackRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_default_rule_and_customer_balance(): void
    {
        $rule = CashbackRule::factory()->create(['is_default' => true, 'status' => true]);
        $customer = Customer::factory()->create();
        CashbackLedger::factory()->create(['customer_id' => $customer->id, 'amount' => 500, 'balance_after' => 500]);

        $repository = new CashbackRepository(new CashbackRule);

        $this->assertSame($rule->id, $repository->defaultRule()->id);
        $this->assertSame(500.0, $repository->customerBalance($customer));
    }
}
