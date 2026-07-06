@extends('layouts.admin')

@section('title', 'Business Contact Settings')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Business Contact Settings</h1>
            <div class="text-muted">Public contact details used in header, footer, and policy pages.</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.settings.business.update') }}" class="row g-3">
                @csrf
                @method('PATCH')

                <div class="col-md-6">
                    <label class="form-label">Business Name</label>
                    <input name="name" value="{{ old('name', $settings['name']) }}" class="form-control @error('name') is-invalid @enderror">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Support Email</label>
                    <input type="email" name="support_email" value="{{ old('support_email', $settings['support_email']) }}" class="form-control @error('support_email') is-invalid @enderror">
                    @error('support_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Support Phone</label>
                    <input name="support_phone" value="{{ old('support_phone', $settings['support_phone']) }}" class="form-control @error('support_phone') is-invalid @enderror">
                    @error('support_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">WhatsApp Number</label>
                    <input name="whatsapp_number" value="{{ old('whatsapp_number', $settings['whatsapp_number']) }}" class="form-control @error('whatsapp_number') is-invalid @enderror">
                    @error('whatsapp_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $settings['address']) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input name="city" value="{{ old('city', $settings['city']) }}" class="form-control @error('city') is-invalid @enderror">
                    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input name="state" value="{{ old('state', $settings['state']) }}" class="form-control @error('state') is-invalid @enderror">
                    @error('state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Pincode</label>
                    <input name="pincode" value="{{ old('pincode', $settings['pincode']) }}" class="form-control @error('pincode') is-invalid @enderror">
                    @error('pincode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Instagram URL</label>
                    <input name="instagram_url" value="{{ old('instagram_url', $settings['instagram_url']) }}" class="form-control @error('instagram_url') is-invalid @enderror">
                    @error('instagram_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Google Maps URL</label>
                    <input name="google_maps_url" value="{{ old('google_maps_url', $settings['google_maps_url']) }}" class="form-control @error('google_maps_url') is-invalid @enderror">
                    @error('google_maps_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Business Hours</label>
                    <input name="business_hours" value="{{ old('business_hours', $settings['business_hours']) }}" class="form-control @error('business_hours') is-invalid @enderror">
                    @error('business_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <button class="btn btn-success">Save Business Settings</button>
                </div>
            </form>
        </div>
    </div>
@endsection
