<footer class="gk-footer">
    <div class="container">
        <div class="gk-footer-main">
            <div class="gk-footer-about">
                <img src="{{ asset('assets/images/logos/logo.png') }}" alt="GrihasthiKart" class="gk-footer-logo">
                <p>Your trusted online grocery store delivering fresh, quality products at best prices straight to your doorstep.</p>
                <div class="gk-footer-points">Fresh Products <span></span> Best Prices <span></span> On-Time Delivery</div>
                <div class="gk-socials">
                    <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://wa.me/" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <div class="gk-footer-newsletter">
                <h2>Stay Updated!</h2>
                <p>Subscribe to get best offers and updates</p>
                <form action="{{ route('products.index') }}" method="GET">
                    <input type="email" class="form-control" placeholder="Enter your email" aria-label="Email address">
                    <button class="btn btn-success w-100 mt-3" type="submit">Subscribe</button>
                </form>
            </div>

            <div>
                <h2>Quick Links</h2>
                <ul class="gk-footer-links">
                    <li><a href="{{ route('products.index') }}"><i class="fa-solid fa-store"></i> Products</a></li>
                    <li><a href="{{ route('categories.index') }}"><i class="fa-solid fa-table-cells-large"></i> Categories</a></li>
                    <li><a href="{{ route('brands.index') }}"><i class="fa-solid fa-tags"></i> Brands</a></li>
                    <li><a href="{{ route('customer.login') }}"><i class="fa-regular fa-user"></i> Customer Login</a></li>
                    <li><a href="{{ route('cart.show') }}"><i class="fa-solid fa-cart-shopping"></i> Cart</a></li>
                </ul>
            </div>

            <div>
                <h2>Information</h2>
                <ul class="gk-footer-links">
                    <li><a href="#"><i class="fa-solid fa-shield-halved"></i> Privacy Policy</a></li>
                    <li><a href="#"><i class="fa-regular fa-file-lines"></i> Terms & Conditions</a></li>
                    <li><a href="#"><i class="fa-solid fa-truck"></i> Shipping & Cancellation Policy</a></li>
                    <li><a href="#"><i class="fa-solid fa-rotate-left"></i> Return & Refund Policy</a></li>
                    <li><a href="#"><i class="fa-solid fa-circle-info"></i> Disclaimer</a></li>
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
            <span>&copy; {{ date('Y') }} GrihasthiKart. All Rights Reserved.</span>
            <span><i class="fa-solid fa-shield-halved"></i> 100% Secure Shopping</span>
            <span>Designed with <i class="fa-solid fa-heart text-danger"></i> in India.</span>
        </div>
    </div>
</footer>
