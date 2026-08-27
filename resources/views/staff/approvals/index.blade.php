@extends('layouts.staff')

@section('title', 'Approvals')

@section('staff-content')
<h1 class="h3 mb-4">Approvals</h1>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Type</th><th>Requester</th><th>Reason</th><th>Status</th><th class="text-end">Decision</th></tr></thead>
            <tbody>
                @forelse ($approvals as $approval)
                    <tr>
                        <td>{{ str($approval->approval_type)->replace('_', ' ')->headline() }}</td>
                        <td>{{ $approval->requester?->name }}</td>
                        <td>{{ $approval->reason_code }}<div class="small text-muted">{{ $approval->notes }}</div></td>
                        <td><span class="badge text-bg-warning">Awaiting Confirmation</span></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('staff.approvals.decide', $approval) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="decision" value="approve"><button class="btn btn-sm btn-success">Approve / Confirm</button></form>
                            <form method="POST" action="{{ route('staff.approvals.decide', $approval) }}" class="d-inline">@csrf @method('PATCH')<input type="hidden" name="decision" value="reject"><button class="btn btn-sm btn-outline-danger">Reject / Not Received</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No approvals waiting.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $approvals->links() }}</div>
@endsection
