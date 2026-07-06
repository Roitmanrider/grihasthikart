<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\ContactMessage;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_contact_page_loads_configured_business_details(): void
    {
        $service = app(BusinessSettingService::class);
        $service->updateBusinessSettings([
            'name' => 'GrihasthiKart',
            'support_email' => 'care@grihasthikart.in',
            'support_phone' => '+91 9876543210',
            'whatsapp_number' => '+91 9123456789',
            'address' => 'Main Road',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'business_hours' => 'Daily, 9 AM - 8 PM',
            'instagram_url' => 'https://instagram.com/grihasthikart',
            'google_maps_url' => 'https://maps.google.com/?q=GrihasthiKart',
        ]);

        $this->get(route('pages.contact'))
            ->assertOk()
            ->assertSee('care@grihasthikart.in')
            ->assertSee('tel:+919876543210', false)
            ->assertSee('https://wa.me/919123456789', false)
            ->assertSee('Main Road')
            ->assertSee('Daily, 9 AM - 8 PM');
    }

    public function test_header_and_footer_contact_links_render_safely_from_settings(): void
    {
        app(BusinessSettingService::class)->updateBusinessSettings([
            'support_phone' => '+91 9876543210',
            'whatsapp_number' => '+91 9123456789',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('tel:+919876543210', false)
            ->assertSee('https://wa.me/919123456789', false);
    }

    public function test_contact_form_validation_and_storage_work(): void
    {
        $this->post(route('contact-messages.store'), [])
            ->assertSessionHasErrors(['name', 'message']);

        $this->post(route('contact-messages.store'), [
            'name' => 'Rohit Kumar',
            'mobile' => '9876543210',
            'email' => 'rohit@example.com',
            'subject' => 'Order support',
            'message' => 'Please help with my grocery order.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Rohit Kumar',
            'email' => 'rohit@example.com',
            'status' => 'new',
        ]);
    }

    public function test_admin_contact_messages_page_requires_auth_and_lists_messages(): void
    {
        ContactMessage::factory()->create([
            'name' => 'Support Customer',
            'message' => 'Need help with delivery.',
        ]);

        $this->get(route('admin.contact-messages.index'))
            ->assertRedirect(route('admin.login'));

        $this->actingAs($this->admin)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertSee('Support Customer');
    }
}
