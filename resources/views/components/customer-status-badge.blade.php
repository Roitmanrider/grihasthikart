@php
    $value = (string) ($status ?? '');
    $normalized = str($value)->lower()->replace(['_', '-'], ' ')->squish()->toString();
    $label = $label ?? str($normalized)->headline()->toString();
    $variant = match (true) {
        in_array($normalized, ['approved', 'delivered', 'refunded', 'paid', 'active'], true) => 'success',
        in_array($normalized, ['pending', 'pending approval', 'processing', 'requested', 'confirmed', 'picking', 'packed', 'out for delivery', 'awaiting', 'received'], true) => 'warning',
        in_array($normalized, ['rejected', 'cancelled', 'canceled', 'cancelled by customer', 'cancelled by admin', 'failed', 'inactive'], true) => 'danger',
        default => 'info',
    };
@endphp

<span class="gk-status-badge gk-status-badge-{{ $variant }}">{{ $label }}</span>
