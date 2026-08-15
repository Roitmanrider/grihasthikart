@extends('layouts.admin')

@section('title','Checkout Settings')

@section('admin-content')
@php
    $durationOptions = [
        15 => '15 minutes',
        30 => '30 minutes',
        45 => '45 minutes',
        60 => '1 hour',
        75 => '1 hour 15 minutes',
        90 => '1 hour 30 minutes',
        105 => '1 hour 45 minutes',
        120 => '2 hours',
    ];
@endphp
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Checkout Settings</h1><div class="text-muted">Service rules for checkout and COD.</div></div>
</div>
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body">
<form method="POST" action="{{ route('admin.settings.checkout.update') }}" class="row g-3">
@csrf @method('PUT')
<div class="col-12"><h2 class="h5 mb-1">Standard Delivery Rules</h2><div class="text-muted small">Used for guests and standard customers unless a customer override applies.</div></div>
<div class="col-md-4"><label class="form-label">Minimum Order Amount</label><input type="number" step="0.01" min="0" name="minimum_order_amount" value="{{ old('minimum_order_amount', $settings['minimum_order_amount']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Delivery Charge</label><input type="number" step="0.01" min="0" name="delivery_charge" value="{{ old('delivery_charge', $settings['delivery_charge']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Free Delivery Threshold</label><input type="number" step="0.01" min="0" name="free_delivery_threshold" value="{{ old('free_delivery_threshold', $settings['free_delivery_threshold']) }}" class="form-control"><div class="form-text">Blank keeps a flat delivery charge. 0 means always free.</div></div>
<div class="col-12"><h2 class="h5 mb-1 mt-2">Premium Delivery Rules</h2><div class="text-muted small">Blank values inherit the Standard delivery rule.</div></div>
<div class="col-md-4"><label class="form-label">Premium Minimum Order</label><input type="number" step="0.01" min="0" name="premium_minimum_order_amount" value="{{ old('premium_minimum_order_amount', $settings['premium_minimum_order_amount']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Premium Delivery Charge</label><input type="number" step="0.01" min="0" name="premium_delivery_charge" value="{{ old('premium_delivery_charge', $settings['premium_delivery_charge']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Premium Free Delivery Threshold</label><input type="number" step="0.01" min="0" name="premium_free_delivery_threshold" value="{{ old('premium_free_delivery_threshold', $settings['premium_free_delivery_threshold']) }}" class="form-control"><div class="form-text">Blank inherits Standard. 0 means always free.</div></div>
<div class="col-12"><hr><h2 class="h5 mb-1">Delivery Scheduling</h2></div>
<div class="col-md-4"><label class="form-label">Today Cutoff Time</label><input type="time" name="today_delivery_cutoff_time" value="{{ old('today_delivery_cutoff_time', $settings['today_delivery_cutoff_time']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Max Delivery Days Ahead</label><input type="number" min="0" name="max_delivery_days_ahead" value="{{ old('max_delivery_days_ahead', $settings['max_delivery_days_ahead']) }}" class="form-control"></div>
<div class="col-12"><hr><h2 class="h5 mb-1">Cart Activity / Reservation Settings</h2><div class="text-muted small">Controls customer reservation timing, reminder stages, and employee follow-up visibility.</div></div>
<div class="col-md-4">
    <label class="form-label">Normal Cart Hold Duration</label>
    <select name="cart_hold_minutes" class="form-select @error('cart_hold_minutes') is-invalid @enderror">
        @foreach ($durationOptions as $minutes => $label)
            <option value="{{ $minutes }}" @selected((int) old('cart_hold_minutes', $settings['cart_hold_minutes']) === $minutes)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="form-text">Active cart reservation window before reserved stock is released.</div>
    @error('cart_hold_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">In-App Reminder After</label>
    <select name="cart_reminder_minutes" class="form-select @error('cart_reminder_minutes') is-invalid @enderror">
        @foreach ($durationOptions as $minutes => $label)
            <option value="{{ $minutes }}" @selected((int) old('cart_reminder_minutes', $settings['cart_reminder_minutes']) === $minutes)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="form-text">Must be before WhatsApp reminder and before cart expiry when enabled.</div>
    @error('cart_reminder_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">WhatsApp Reminder After</label>
    <select name="cart_whatsapp_reminder_minutes" class="form-select @error('cart_whatsapp_reminder_minutes') is-invalid @enderror">
        @foreach ($durationOptions as $minutes => $label)
            <option value="{{ $minutes }}" @selected((int) old('cart_whatsapp_reminder_minutes', $settings['cart_whatsapp_reminder_minutes']) === $minutes)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="form-text">Used only when automatic WhatsApp reminders are enabled and configured.</div>
    @error('cart_whatsapp_reminder_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
    <label class="form-label">Daily Offer Reservation Duration</label>
    <select name="daily_offer_hold_minutes" class="form-select @error('daily_offer_hold_minutes') is-invalid @enderror">
        @foreach ($durationOptions as $minutes => $label)
            <option value="{{ $minutes }}" @selected((int) old('daily_offer_hold_minutes', $settings['daily_offer_hold_minutes']) === $minutes)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="form-text">Daily Offer reservations use this stricter window.</div>
    @error('daily_offer_hold_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4"><label class="form-label">Return Window Days</label><input type="number" min="0" max="30" name="return_window_days" value="{{ old('return_window_days', $settings['return_window_days']) }}" class="form-control @error('return_window_days') is-invalid @enderror"><div class="form-text">Counted from the delivered timestamp.</div>@error('return_window_days')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
<div class="col-md-4"><label class="form-label">Default City</label><input name="default_city" value="{{ old('default_city', $settings['default_city']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Default State</label><input name="default_state" value="{{ old('default_state', $settings['default_state']) }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Contact Mobile</label><input name="store_contact_mobile" value="{{ old('store_contact_mobile', $settings['store_contact_mobile']) }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">WhatsApp Number</label><input name="store_whatsapp_number" value="{{ old('store_whatsapp_number', $settings['store_whatsapp_number']) }}" class="form-control"></div>
@foreach (['cod_enabled'=>'COD Enabled','today_delivery_enabled'=>'Today Delivery Enabled','custom_delivery_date_enabled'=>'Custom Delivery Date Enabled','cart_reminder_enabled'=>'Customer In-App Reminder Enabled','cart_whatsapp_reminder_enabled'=>'Automatic WhatsApp Cart Reminder','cart_employee_followup_enabled'=>'Employee Cart Follow-up','cart_abuse_monitoring_enabled'=>'Abuse / Reservation Monitoring','customer_credit_redemption_enabled'=>'Customer Credit Redemption Enabled','customer_invoice_enabled'=>'Customer Invoice Printing Enabled'] as $field => $label)
<div class="col-md-4"><div class="form-check mt-2"><input type="hidden" name="{{ $field }}" value="0"><input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(old($field, $settings[$field]))><label class="form-check-label" for="{{ $field }}">{{ $label }}</label></div></div>
@endforeach
<div class="col-12"><div class="alert alert-light border mb-0">When enabled and WhatsApp messaging is configured, eligible customers receive an automated cart reminder before their reservation expires.</div></div>
<div class="col-12"><button class="btn btn-success">Save Settings</button></div>
</form>
</div></div>
@endsection
