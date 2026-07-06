<?php

namespace Tests\Feature;

use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BusinessSettingSeeder::class);
    }

    public function test_public_policy_pages_load(): void
    {
        foreach ([
            'pages.about' => 'About Us',
            'pages.privacy' => 'Privacy Policy',
            'pages.terms' => 'Terms & Conditions',
            'pages.shipping' => 'Shipping & Cancellation Policy',
            'pages.returns' => 'Return & Refund Policy',
            'pages.disclaimer' => 'Disclaimer',
            'pages.faqs' => 'FAQs',
            'pages.support' => 'Customer Support',
        ] as $route => $heading) {
            $this->get(route($route))
                ->assertOk()
                ->assertSee($heading);
        }
    }

    public function test_footer_policy_links_point_to_valid_routes(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('pages.privacy'), false)
            ->assertSee(route('pages.terms'), false)
            ->assertSee(route('pages.shipping'), false)
            ->assertSee(route('pages.returns'), false)
            ->assertSee(route('pages.disclaimer'), false)
            ->assertSee(route('pages.faqs'), false)
            ->assertSee(route('pages.support'), false)
            ->assertDontSee('href="#"', false);
    }
}
