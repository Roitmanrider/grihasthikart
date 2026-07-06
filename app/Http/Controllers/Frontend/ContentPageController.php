<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContentPageController extends Controller
{
    public function __construct(
        private readonly BusinessSettingService $settingService
    ) {}

    public function page(string $page)
    {
        abort_unless(array_key_exists($page, $this->pages()), 404);

        return view('frontend.pages.show', [
            'page' => $this->pages()[$page],
            'business' => $this->settingService->businessSettings(),
            'whatsappUrl' => $this->settingService->whatsappUrl(),
            'phoneUrl' => $this->settingService->phoneUrl(),
        ]);
    }

    public function contact()
    {
        return view('frontend.pages.contact', [
            'business' => $this->settingService->businessSettings(),
            'whatsappUrl' => $this->settingService->whatsappUrl(),
            'phoneUrl' => $this->settingService->phoneUrl(),
        ]);
    }

    public function storeContact(StoreContactMessageRequest $request)
    {
        ContactMessage::query()->create(array_merge($request->validated(), [
            'status' => 'new',
        ]));

        return back()->with('success', 'Thank you. Our support team will get back to you soon.');
    }

    public function faqs(Request $request)
    {
        return $this->page('faqs');
    }

    private function pages(): array
    {
        return [
            'about-us' => [
                'title' => 'About Us',
                'description' => 'Learn about GrihasthiKart and our grocery delivery promise.',
                'sections' => [
                    ['About GrihasthiKart', 'GrihasthiKart is an online grocery store focused on making everyday household shopping simple, transparent, and convenient for local customers.'],
                    ['Our Promise', 'We aim to offer practical grocery choices, clear pricing, reliable delivery slots, and responsive customer support. Product availability, offers, and delivery coverage may change as operations grow.'],
                ],
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'description' => 'How GrihasthiKart handles basic customer information.',
                'sections' => [
                    ['Information We Collect', 'We may collect your name, mobile number, email, address, order details, and payment-related references needed to provide grocery ordering and delivery services.'],
                    ['How We Use Information', 'Information is used to process orders, provide support, improve service quality, and meet legal or operational requirements. We do not publish customer contact details publicly.'],
                    ['Policy Updates', 'This policy may be updated as the service evolves. Continued use of the website means you accept the latest version shown here.'],
                ],
            ],
            'terms-and-conditions' => [
                'title' => 'Terms & Conditions',
                'description' => 'Basic terms for using GrihasthiKart.',
                'sections' => [
                    ['Using The Website', 'Customers should provide accurate contact, address, and order information. Orders may be accepted, declined, rescheduled, or cancelled based on product availability and service constraints.'],
                    ['Pricing And Availability', 'Prices, offers, product images, and stock availability may change without prior notice. Final payable amount is shown during checkout or confirmed order processing.'],
                    ['Account Responsibility', 'Customers are responsible for the information submitted through their account or checkout session.'],
                ],
            ],
            'shipping-and-cancellation' => [
                'title' => 'Shipping & Cancellation Policy',
                'description' => 'Delivery slot and cancellation basics.',
                'sections' => [
                    ['Delivery', 'Orders are delivered according to available delivery slots and serviceability. Delivery timing may vary due to stock, weather, traffic, or operational constraints.'],
                    ['Cancellation', 'Cancellation requests should be raised as early as possible. Orders already packed, dispatched, or delivered may not be eligible for cancellation.'],
                    ['Service Updates', 'Delivery rules and service areas may be updated as operations expand.'],
                ],
            ],
            'return-and-refund' => [
                'title' => 'Return & Refund Policy',
                'description' => 'Return and refund basics for grocery orders.',
                'sections' => [
                    ['Returns', 'Return eligibility depends on product type, condition, packaging, and time of reporting. Perishable, opened, or used products may not be returnable unless there is a verified quality or delivery issue.'],
                    ['Refunds', 'Approved refunds may be processed through available store-approved methods. Timelines can vary depending on payment mode and verification.'],
                    ['Report Issues', 'Customers should report missing, damaged, or incorrect items promptly with order details.'],
                ],
            ],
            'disclaimer' => [
                'title' => 'Disclaimer',
                'description' => 'General website and catalog disclaimer.',
                'sections' => [
                    ['Catalog Information', 'We try to keep product information, images, pricing, and availability accurate, but occasional errors or delays may occur.'],
                    ['No Professional Advice', 'Product descriptions and general content are for shopping assistance only and should not be treated as medical, legal, financial, or professional advice.'],
                    ['Updates', 'Website content and policies may be updated without prior notice.'],
                ],
            ],
            'customer-support' => [
                'title' => 'Customer Support',
                'description' => 'How to get help with GrihasthiKart orders.',
                'sections' => [
                    ['Order Help', 'For order, delivery, payment proof, coupon, cashback, or account questions, contact support using the phone, WhatsApp, email, or contact form on this website.'],
                    ['Response Time', 'We aim to respond during business hours. Complex issues may require additional verification.'],
                ],
            ],
            'faqs' => [
                'title' => 'FAQs',
                'description' => 'Frequently asked questions about GrihasthiKart.',
                'sections' => [
                    ['How do I place an order?', 'Browse products, add available variants to cart, review your cart, and complete checkout with delivery details.'],
                    ['What are delivery slots?', 'Delivery slots are available time windows for receiving your order. Same-day slots depend on order time and operational availability.'],
                    ['What payment methods are available?', 'Cash on Delivery is available when enabled. QR or online payment options may appear when configured by the store.'],
                    ['How does cashback work?', 'Eligible customers may earn cashback according to active store rules. Cashback visibility and redemption depend on account status and approved rules.'],
                    ['How do I use coupons?', 'Enter a valid coupon code on the cart page. Coupon eligibility depends on order value, customer rules, validity, and usage limits.'],
                    ['How can I cancel an order?', 'Contact support as early as possible. Orders already packed, dispatched, or delivered may not be cancellable.'],
                    ['What is the return/refund process?', 'Report product issues promptly with order details. Return and refund eligibility depends on product condition and store verification.'],
                ],
            ],
        ];
    }
}
