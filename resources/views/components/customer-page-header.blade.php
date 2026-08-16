<div class="gk-account-page-header">
    <div class="min-w-0">
        <h1 class="h3 mb-1">{{ $title }}</h1>
        @isset($subtitle)
            <div class="text-muted text-break">{{ $subtitle }}</div>
        @endisset
    </div>
    @isset($actions)
        <div class="gk-account-page-actions">
            {{ $actions }}
        </div>
    @endisset
</div>
