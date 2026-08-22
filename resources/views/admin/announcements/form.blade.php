@extends('layouts.admin')

@php
    $selectedStores = old('store_ids', $announcement->stores?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? []);
    $selectedCustomers = old('customer_ids', $announcement->customers?->pluck('id')->map(fn ($id) => (string) $id)->all() ?? []);
@endphp

@section('title', $announcement->exists ? 'Edit Announcement' : 'New Announcement')

@section('admin-content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">{{ $announcement->exists ? 'Edit Announcement' : 'New Announcement' }}</h1>
    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

@if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif

<form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="card border-0 shadow-sm">
    @csrf
    @if ($announcement->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $announcement->title) }}"></div>
        <div class="col-md-3"><label class="form-label">Priority</label><input type="number" name="priority" min="0" max="9999" class="form-control" value="{{ old('priority', $announcement->priority ?? 0) }}" required></div>
        <div class="col-md-3"><label class="form-label">Audience</label><select name="audience_type" class="form-select"><option value="all" @selected(old('audience_type', $announcement->audience_type ?: 'all') === 'all')>All registered customers</option><option value="stores" @selected(old('audience_type', $announcement->audience_type) === 'stores')>Selected stores</option><option value="customers" @selected(old('audience_type', $announcement->audience_type) === 'customers')>Selected customers</option></select></div>
        <div class="col-12"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="4" required>{{ old('message', $announcement->message) }}</textarea></div>
        <div class="col-md-6"><label class="form-label">Stores</label><select name="store_ids[]" class="form-select" multiple size="6">@foreach($stores as $store)<option value="{{ $store->id }}" @selected(in_array((string) $store->id, $selectedStores, true))>{{ $store->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Customers</label><select name="customer_ids[]" class="form-select" multiple size="6">@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(in_array((string) $customer->id, $selectedCustomers, true))>{{ $customer->name }} - {{ $customer->mobile }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label">Start</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', $announcement->starts_at?->format('Y-m-d\\TH:i')) }}"></div>
        <div class="col-md-3"><label class="form-label">End</label><input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', $announcement->ends_at?->format('Y-m-d\\TH:i')) }}"></div>
        <div class="col-md-3"><label class="form-label">CTA Text</label><input name="cta_text" class="form-control" value="{{ old('cta_text', $announcement->cta_text) }}"></div>
        <div class="col-md-3"><label class="form-label">CTA URL</label><input name="cta_url" class="form-control" value="{{ old('cta_url', $announcement->cta_url) }}"></div>
        <div class="col-12 d-flex flex-wrap gap-4">
            <label class="form-check"><input type="hidden" name="enabled" value="0"><input class="form-check-input" type="checkbox" name="enabled" value="1" @checked(old('enabled', $announcement->enabled ?? true))> Active</label>
            <label class="form-check"><input type="hidden" name="sticky" value="0"><input class="form-check-input" type="checkbox" name="sticky" value="1" @checked(old('sticky', $announcement->sticky))> Sticky</label>
            <label class="form-check"><input type="hidden" name="dismissible" value="0"><input class="form-check-input" type="checkbox" name="dismissible" value="1" @checked(old('dismissible', $announcement->dismissible ?? true))> Dismissible</label>
        </div>
    </div>
    <div class="card-footer bg-white text-end"><button class="btn btn-success">Save Announcement</button></div>
</form>
@endsection
