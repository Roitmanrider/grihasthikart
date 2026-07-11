@extends('layouts.frontend')

@section('title', 'My Notifications')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">My Notifications</h1>
                <div class="text-muted">{{ $customer->mobile }}</div>
            </div>
            <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                @csrf
                @method('PATCH')
                <button class="btn btn-outline-success" type="submit">Read all</button>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="list-group list-group-flush">
                @forelse ($notifications as $notification)
                    <div class="list-group-item py-3 {{ $notification->read_at ? '' : 'bg-success-subtle' }}">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <div class="fw-semibold">{{ $notification->title }}</div>
                                @if ($notification->message)
                                    <div class="text-muted small mt-1">{{ $notification->message }}</div>
                                @endif
                                <div class="small text-muted mt-2">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <span class="badge {{ $notification->read_at ? 'text-bg-light' : 'text-bg-success' }}">
                                    {{ $notification->read_at ? 'Read' : 'Unread' }}
                                </span>
                                @if ($notification->action_url)
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ $notification->action_url }}">Open</a>
                                @endif
                                @unless ($notification->read_at)
                                    <form method="POST" action="{{ route('customer.notifications.read', $notification) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-success" type="submit">Mark read</button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No notifications yet.</div>
                @endforelse
            </div>
            <div class="card-footer bg-white">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
