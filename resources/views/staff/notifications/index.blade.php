@extends('layouts.staff')

@section('title', 'Staff Notifications')

@section('staff-content')
<div class="d-flex justify-content-between mb-4">
    <h1 class="h3">Staff Notifications</h1>
    <form method="POST" action="{{ route('staff.notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-outline-secondary">Mark all read</button></form>
</div>
<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
        @forelse ($notifications as $notification)
            <div class="list-group-item d-flex justify-content-between gap-3">
                <div>
                    <div class="fw-semibold">{{ $notification->title }}</div>
                    <div class="small text-muted">{{ str($notification->workstream)->headline() }} / {{ $notification->message }}</div>
                </div>
                <form method="POST" action="{{ route('staff.notifications.read', $notification) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success" @disabled($notification->read_at)>Read</button></form>
            </div>
        @empty
            <div class="list-group-item text-center text-muted py-4">No notifications.</div>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection
