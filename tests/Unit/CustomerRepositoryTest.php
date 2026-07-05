<?php

namespace Tests\Unit;

use App\Domains\Customer\Repositories\CustomerRepository;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_customers_by_search_and_flags(): void
    {
        $matched = Customer::factory()->create(['name' => 'Rohit Kumar', 'mobile' => '9876543210', 'status' => true, 'is_premium' => true]);
        Customer::factory()->create(['name' => 'Other Customer', 'status' => false]);

        $customers = (new CustomerRepository(new Customer))->paginatedList([
            'search' => 'Rohit',
            'status' => 1,
            'is_premium' => 1,
        ]);

        $this->assertTrue($customers->getCollection()->contains('id', $matched->id));
        $this->assertCount(1, $customers->getCollection());
    }
}
