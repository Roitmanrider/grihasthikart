@extends('layouts.staff')

@section('title', $title ?? 'Task Queue')

@section('staff-content')
<h1 class="h3 mb-4">{{ $title ?? 'Task Queue' }}</h1>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Order</th><th>Customer</th><th>Slot</th><th>Assigned</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->order?->order_number }}</td>
                        <td>{{ $assignment->order?->customer_name }}</td>
                        <td>{{ $assignment->order?->delivery_slot ?: '-' }}</td>
                        <td>{{ $assignment->assignee?->name ?: 'Unassigned' }}</td>
                        <td><span class="badge text-bg-light">{{ str($assignment->status)->headline() }}</span></td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('staff.assignments.start', $assignment) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Start</button></form>
                            <form method="POST" action="{{ route('staff.assignments.complete', $assignment) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Complete</button></form>
                            @if ($assignment->task_type === 'DELIVERY')
                                <form method="POST" action="{{ route('staff.deliveries.start', $assignment) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary">Out for Delivery</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No tasks in this queue.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $assignments->links() }}</div>
@endsection
