@props([
    'title' => 'Nothing to show yet',
    'message' => 'New records will appear here once available.',
    'action' => null,
    'actionLabel' => null,
])

<div class="border rounded bg-light p-4 text-center">
    <div class="text-success mb-2"><i class="fa-solid fa-circle-info fa-lg"></i></div>
    <div class="fw-semibold">{{ $title }}</div>
    <div class="text-muted small">{{ $message }}</div>
    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="btn btn-sm btn-outline-success mt-3">{{ $actionLabel }}</a>
    @endif
</div>
