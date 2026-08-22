@php
    $noticeCustomer = app(\App\Domains\Customer\Services\CustomerAuthService::class)->currentCustomer(request()->session());
    $announcement = app(\App\Domains\Marketing\Services\CustomerAnnouncementService::class)->applicableFor($noticeCustomer);
@endphp

<div data-account-notice-strip>
@if ($announcement)
    <div class="gk-account-notice-strip alert {{ $announcement->sticky ? 'alert-success' : 'alert-light border' }} d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            @if ($announcement->title)
                <strong>{{ $announcement->title }}</strong>
            @endif
            <span>{{ $announcement->message }}</span>
            @if ($announcement->cta_text && $announcement->cta_url)
                <a href="{{ $announcement->cta_url }}" class="ms-2">{{ $announcement->cta_text }}</a>
            @endif
        </div>
        @if ($announcement->dismissible)
            <form method="POST" action="{{ route('customer.announcements.dismiss', $announcement) }}">
                @csrf
                <button class="btn btn-sm btn-outline-secondary">Dismiss</button>
            </form>
        @endif
    </div>
@endif
</div>
