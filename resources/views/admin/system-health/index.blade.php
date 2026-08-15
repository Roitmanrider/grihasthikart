@extends('layouts.admin')

@section('title', 'System Health')

@section('admin-content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">System Health</h1>
        <div class="text-muted">Operational checks without exposing secrets.</div>
    </div>
    <span class="badge text-bg-light border rounded-pill px-3 py-2">
        {{ now(config('app.timezone'))->format('d M Y, h:i A T') }}
    </span>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Check</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($checks as $check)
                    <tr>
                        <td class="fw-semibold">{{ $check['label'] }}</td>
                        <td>
                            <span class="badge {{ $check['ok'] ? 'text-bg-success' : 'text-bg-warning' }} rounded-pill px-3 py-2">
                                {{ $check['ok'] ? 'OK' : 'Needs Attention' }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $check['value'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
