<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandedErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_invalid_route_returns_branded_404_page(): void
    {
        $this->get('/missing-grocery-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Continue Shopping');
    }

    public function test_env_file_is_not_exposed(): void
    {
        $this->get('/.env')
            ->assertNotFound()
            ->assertDontSee('APP_KEY');
    }
}
