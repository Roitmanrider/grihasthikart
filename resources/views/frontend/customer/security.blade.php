@extends('layouts.frontend')

@section('title', 'Account Security')

@section('content')
<section class="py-5">
    <div class="container">
        @include('frontend.customer.account-nav')

        <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Account Security</h1>
                <div class="text-muted">Signed-in devices for {{ $customer->mobile }}.</div>
            </div>
            <form method="POST" action="{{ route('customer.security.sessions.destroy-others') }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger">Logout Other Sessions</button>
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="list-group list-group-flush">
                @forelse ($sessions as $session)
                    <div class="list-group-item py-3">
                        <div class="d-flex flex-wrap justify-content-between gap-3">
                            <div>
                                <div class="fw-semibold">{{ $session->device_label ?: 'Signed-in device' }}</div>
                                <div class="small text-muted">
                                    Last seen {{ $session->last_seen_at?->format('d M Y, h:i A') ?: 'unknown' }}
                                    @if ($session->ip_address)
                                        from {{ $session->ip_address }}
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-start">
                                @if ($currentSession && $currentSession->id === $session->id)
                                    <span class="badge text-bg-success rounded-pill px-3 py-2">Current Session</span>
                                @endif
                                <span class="badge {{ $session->revoked_at ? 'text-bg-secondary' : 'text-bg-light border' }} rounded-pill px-3 py-2">
                                    {{ $session->revoked_at ? 'Signed Out' : 'Active' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No session records yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
