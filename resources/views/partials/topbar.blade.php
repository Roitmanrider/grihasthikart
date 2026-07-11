@php
    $adminUnreadNotifications = app(\App\Domains\Notification\Services\NotificationService::class)->adminUnreadCount();
@endphp

<div class="bg-success text-white px-3 px-md-4 py-2">

    <div class="d-flex flex-column flex-md-row gap-1 align-items-md-center justify-content-between">

        <div>

            <div class="fw-semibold">Fresh grocery catalog</div>

            <div class="small opacity-75">Fresh groceries, fair prices, simple checkout</div>

        </div>

        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.notifications.index') }}" class="text-white text-decoration-none position-relative" aria-label="Notifications">
                <i class="fa-regular fa-bell"></i>
                @if ($adminUnreadNotifications > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-light text-success">
                        {{ $adminUnreadNotifications }}
                    </span>
                @endif
            </a>
            <div class="small">COD, QR and coupon-ready MVP</div>
        </div>

    </div>

</div>
