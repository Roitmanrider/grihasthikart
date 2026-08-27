@php
    $address = $address ?? null;
@endphp

<div class="col-md-4">
    <label class="form-label">Label</label>
    <input name="label" value="{{ old('label', $address?->label) }}" class="form-control form-control-sm" placeholder="Home / Office">
</div>
<div class="col-md-4">
    <label class="form-label">Recipient</label>
    <input name="recipient_name" value="{{ old('recipient_name', $address?->recipient_name ?? $customer->name) }}" class="form-control form-control-sm" required>
</div>
<div class="col-md-4">
    <label class="form-label">Mobile</label>
    <input name="mobile" value="{{ old('mobile', $address?->mobile ?? $customer->mobile) }}" class="form-control form-control-sm" required>
</div>
<div class="col-12">
    <label class="form-label">Address Line 1</label>
    <input name="address_line1" value="{{ old('address_line1', $address?->address_line1) }}" class="form-control form-control-sm" required>
</div>
<div class="col-12">
    <label class="form-label">Address Line 2</label>
    <input name="address_line2" value="{{ old('address_line2', $address?->address_line2) }}" class="form-control form-control-sm">
</div>
<div class="col-md-4">
    <label class="form-label">City</label>
    <input name="city" value="{{ old('city', $address?->city) }}" class="form-control form-control-sm" required>
</div>
<div class="col-md-4">
    <label class="form-label">State</label>
    <input name="state" value="{{ old('state', $address?->state) }}" class="form-control form-control-sm" required>
</div>
<div class="col-md-4">
    <label class="form-label">Pincode</label>
    <input name="pincode" value="{{ old('pincode', $address?->pincode) }}" class="form-control form-control-sm" required>
</div>
<div class="col-12">
    <label class="form-label">Landmark</label>
    <input name="landmark" value="{{ old('landmark', $address?->landmark) }}" class="form-control form-control-sm">
</div>
<div class="col-md-4">
    <label class="form-label">Latitude</label>
    <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $address?->latitude) }}" class="form-control form-control-sm">
</div>
<div class="col-md-4">
    <label class="form-label">Longitude</label>
    <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $address?->longitude) }}" class="form-control form-control-sm">
</div>
<div class="col-md-4">
    <label class="form-label">Geofence Radius (m)</label>
    <input type="number" min="25" max="5000" name="geofence_radius_meters" value="{{ old('geofence_radius_meters', $address?->geofence_radius_meters) }}" class="form-control form-control-sm">
</div>
<div class="col-md-4">
    <div class="form-check">
        <input type="hidden" name="is_approved" value="0">
        <input class="form-check-input" type="checkbox" name="is_approved" value="1" id="addressApproved{{ $address?->id ?? 'New' }}" @checked(old('is_approved', $address?->is_approved ?? true))>
        <label class="form-check-label" for="addressApproved{{ $address?->id ?? 'New' }}">Approved</label>
    </div>
</div>
<div class="col-md-4">
    <div class="form-check">
        <input type="hidden" name="status" value="0">
        <input class="form-check-input" type="checkbox" name="status" value="1" id="addressActive{{ $address?->id ?? 'New' }}" @checked(old('status', $address?->status ?? true))>
        <label class="form-check-label" for="addressActive{{ $address?->id ?? 'New' }}">Active</label>
    </div>
</div>
<div class="col-md-4">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="addressDefault{{ $address?->id ?? 'New' }}" @checked(old('is_default', $address?->is_default ?? false))>
        <label class="form-check-label" for="addressDefault{{ $address?->id ?? 'New' }}">Default</label>
    </div>
</div>
<div class="col-12">
    <label class="form-label">Rejection reason</label>
    <input name="rejection_reason" value="{{ old('rejection_reason', $address?->rejection_reason) }}" class="form-control form-control-sm" placeholder="Used only when address is not approved">
</div>
