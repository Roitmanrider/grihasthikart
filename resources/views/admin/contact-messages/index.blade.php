@extends('layouts.admin')

@section('title', 'Contact Messages')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Contact Messages</h1>
            <div class="text-muted">Customer enquiries submitted from the Contact Us page.</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Received</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($messages as $message)
                        <tr>
                            <td class="fw-semibold">{{ $message->name }}</td>
                            <td>
                                <div>{{ $message->mobile ?: 'No mobile' }}</div>
                                <div class="small text-muted">{{ $message->email ?: 'No email' }}</div>
                            </td>
                            <td>{{ $message->subject ?: 'General enquiry' }}</td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($message->message, 90) }}</td>
                            <td><span class="badge text-bg-success">{{ ucfirst($message->status) }}</span></td>
                            <td>{{ $message->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">No contact messages yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $messages->links() }}
        </div>
    </div>
@endsection
