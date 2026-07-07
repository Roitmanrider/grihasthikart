@php
    $settingService = app(\App\Domains\Setting\Services\BusinessSettingService::class);
    $business = $settingService->businessSettings();
    $whatsappUrl = $settingService->whatsappUrl();
@endphp

<footer class="gk-footer">
    <div class="container">
        <div class="gk-footer-main">
            <div class="gk-footer-about">
                <img src="{{ asset('assets/images/logos/logo.png') }}" alt="GrihasthiKart" class="gk-footer-logo">
                <p>{{ $business['name'] }} is your trusted online grocery store for fresh, quality products and everyday household essentials.</p>
                <div class="gk-footer-points">Fresh Products <span></span> Best Prices <span></span> On-Time Delivery</div>
                <div class="gk-socials">
                    @if ($business['instagram_url'])
                        <a href="{{ $business['instagram_url'] }}" aria-label="Instagram" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a>
                    @endif
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" aria-label="WhatsApp" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i></a>
                    @endif
                </div>
            </div>

            <div class="gk-footer-newsletter">
                <h2>Let’s get in touch</h2>
                <p>If you have any questions, just ask!</p>
                <form action="{{ route('contact-messages.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="name" value="Footer Visitor">
                    <input type="email" name="email" class="form-control mb-3" placeholder="Enter your email" aria-label="Email address">
                    <input type="text" name="message" class="form-control" placeholder="Type your message" aria-label="Message">
                    <button class="btn btn-success w-100 mt-3" type="submit">Submit <i class="fa-regular fa-paper-plane ms-2"></i></button>
                </form>
            </div>

            <div>
                <h2>Quick Links</h2>
                <ul class="gk-footer-links">
                    <li><a href="{{ route('pages.about') }}"><i class="fa-regular fa-circle-question"></i> About Us</a></li>
                    <li><a href="{{ route('pages.about') }}#happy-customers"><i class="fa-solid fa-users"></i> Happy Customers</a></li>
                    <li><a href="{{ route('pages.support') }}"><i class="fa-solid fa-headset"></i> Customer Support</a></li>
                    <li><a href="{{ $business['support_email'] ? 'mailto:'.$business['support_email'] : route('pages.contact') }}"><i class="fa-regular fa-envelope"></i> Email Us</a></li>
                    <li><a href="{{ route('pages.faqs') }}"><i class="fa-regular fa-circle-question"></i> FAQs</a></li>
                </ul>
            </div>

            <div>
                <h2>Information</h2>
                <ul class="gk-footer-links">
                    <li><a href="{{ route('pages.privacy') }}"><i class="fa-solid fa-shield-halved"></i> Privacy Policy</a></li>
                    <li><a href="{{ route('pages.terms') }}"><i class="fa-regular fa-file-lines"></i> Terms & Conditions</a></li>
                    <li><a href="{{ route('pages.shipping') }}"><i class="fa-solid fa-truck"></i> Shipping & Cancellation Policy</a></li>
                    <li><a href="{{ route('pages.returns') }}"><i class="fa-solid fa-rotate-left"></i> Return & Refund Policy</a></li>
                    <li><a href="{{ route('pages.disclaimer') }}"><i class="fa-solid fa-circle-info"></i> Disclaimer</a></li>
                    <li><a href="{{ route('pages.faqs') }}"><i class="fa-regular fa-circle-question"></i> FAQs</a></li>
                    <li><a href="{{ route('pages.support') }}"><i class="fa-solid fa-headset"></i> Customer Support</a></li>
                </ul>
            </div>
        </div>

        <div class="gk-payment-row">
            <span>Razorpay</span>
            <span>UPI</span>
            <span>VISA</span>
            <span>Mastercard</span>
            <span>RuPay</span>
            <span><i class="fa-solid fa-money-bill-wave"></i> Cash on Delivery</span>
        </div>

        <div class="gk-footer-bottom">
            <span>&copy; {{ date('Y') }} {{ $business['name'] }}. All Rights Reserved.</span>
            <span><i class="fa-solid fa-shield-halved"></i> 100% Secure Shopping</span>
            <span>Designed with <i class="fa-solid fa-heart text-danger"></i> in India.</span>
        </div>
    </div>
</footer>
