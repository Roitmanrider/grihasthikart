@extends('layouts.admin')

@section('title', 'Customer Announcements')

@section('admin-content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Customer Announcements</h1>
        <div class="text-muted">Compact audience rules for account notice strips.</div>
    </div>
    <a href="{{ route('admin.announcements.create') }}" class="btn btn-success">New Announcement</a>
</div>

@if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Message</th><th>Audience</th><th>Status</th><th>Priority</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @forelse ($announcements as $announcement)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $announcement->title ?: 'Untitled announcement' }}</div>
                        <div class="small text-muted">{{ str($announcement->message)->limit(110) }}</div>
                    </td>
                    <td>
                        {{ str($announcement->audience_type)->headline() }}
                        <div class="small text-muted">{{ $announcement->stores_count }} stores / {{ $announcement->customers_count }} customers</div>
                    </td>
                    <td>
                        <span class="badge {{ $announcement->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $announcement->enabled ? 'Active' : 'Inactive' }}</span>
                        @if ($announcement->sticky)<span class="badge text-bg-info">Sticky</span>@endif
                        @if ($announcement->dismissible)<span class="badge text-bg-light border">Dismissible</span>@endif
                    </td>
                    <td>{{ $announcement->priority }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-success">Edit</a>
                        <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" class="d-inline">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-secondary">Deactivate</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No announcements configured.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($announcements->hasPages()) <div class="card-footer bg-white">{{ $announcements->links() }}</div> @endif
</div>
@endsection
