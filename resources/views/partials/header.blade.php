<header>

    <div class="container">

        <div class="header-wrapper">

            {{-- Logo --}}

            <div class="logo">

                <a href="/">

                    <img
                        src="{{ asset('assets/images/logos/logo.png') }}"
                        alt="GrihasthiKart">

                </a>

            </div>

            {{-- Search --}}

            <div class="search-box">

                <input
                    type="text"
                    placeholder="Search for products, categories, subcategories...">

                <button>

                    <i class="fa-solid fa-magnifying-glass"></i>

                </button>

            </div>

            {{-- Right Side --}}

            <div class="header-actions">

                {{-- Account --}}

                <div class="action-item">

                    <i class="fa-regular fa-user"></i>

                    <span>

                        Hi, Rohit

                    </span>

                </div>

                {{-- Wishlist --}}

                <div class="action-item">

                    <span class="badge">8</span>

                    <i class="fa-regular fa-heart"></i>

                    <span>

                        Wishlist

                    </span>

                </div>

                {{-- Cart --}}

                <div class="action-item">

                    <span class="badge">3</span>

                    <i class="fa-solid fa-cart-shopping"></i>

                    <span>

                        Cart

                    </span>

                </div>

                {{-- WhatsApp --}}

                <div class="action-item">

                    <i class="fa-brands fa-whatsapp whatsapp"></i>

                    <span>

                        WhatsApp

                    </span>

                </div>

                {{-- Phone --}}

                <div class="phone-box">

                    <i class="fa-solid fa-phone"></i>

                    <div>

                        <small>

                            Call Us

                        </small>

                        <strong>

                            1800-123-4567

                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>

</header>
