@extends('layouts.admin')

@section('title', 'Payment Settings')

@section('admin-content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-1">Payment Settings</h1>
        <div class="text-muted">Enable payment methods and configure QR/Razorpay-ready details.</div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('admin.settings.payments.update') }}" enctype="multipart/form-data" class="card border-0 shadow-sm">
    @csrf
    @method('PATCH')
    <div class="card-body row g-3">
        <div class="col-md-4 form-check ms-3">
            <input type="checkbox" name="cod_enabled" value="1" class="form-check-input" id="cod_enabled" @checked(old('cod_enabled', $settings['cod_enabled']))>
            <label for="cod_enabled" class="form-check-label">COD Enabled</label>
        </div>
        <div class="col-md-4 form-check ms-3">
            <input type="checkbox" name="qr_enabled" value="1" class="form-check-input" id="qr_enabled" @checked(old('qr_enabled', $settings['qr_enabled']))>
            <label for="qr_enabled" class="form-check-label">QR Enabled</label>
        </div>
        <div class="col-md-4 form-check ms-3">
            <input type="checkbox" name="razorpay_enabled" value="1" class="form-check-input" id="razorpay_enabled" @checked(old('razorpay_enabled', $settings['razorpay_enabled']))>
            <label for="razorpay_enabled" class="form-check-label">Razorpay Enabled</label>
        </div>

        <div class="col-md-6">
            <label class="form-label">QR Label</label>
            <input type="text" name="qr_label" value="{{ old('qr_label', $settings['qr_label']) }}" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Currency</label>
            <input type="text" name="currency" value="{{ old('currency', $settings['currency']) }}" class="form-control" maxlength="3" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">QR UPI ID</label>
            <input type="text" name="qr_upi_id" value="{{ old('qr_upi_id', $settings['qr_upi_id']) }}" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">QR Display Name</label>
            <input type="text" name="qr_display_name" value="{{ old('qr_display_name', $settings['qr_display_name']) }}" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">QR Image</label>
            <input type="file" name="qr_image" class="form-control" accept="image/*">
            @if ($settings['qr_image_path'])
                <div class="small text-muted mt-1">Current QR image configured.</div>
            @endif
        </div>
        <div class="col-md-6">
            <label class="form-label">Razorpay Key ID</label>
            <input type="text" name="razorpay_key_id" value="{{ old('razorpay_key_id', $settings['razorpay_key_id']) }}" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Razorpay Key Secret</label>
            <input type="password" name="razorpay_key_secret" class="form-control" autocomplete="new-password" placeholder="Leave blank to keep existing secret">
        </div>
    </div>
    <div class="card-footer bg-white">
        <button class="btn btn-success">Save Payment Settings</button>
    </div>
</form>
@endsection
