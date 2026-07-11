@extends('layouts.admin')

@section('title','Checkout Settings')

@section('admin-content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Checkout Settings</h1><div class="text-muted">Service rules for checkout and COD.</div></div>
</div>
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card border-0 shadow-sm"><div class="card-body">
<form method="POST" action="{{ route('admin.settings.checkout.update') }}" class="row g-3">
@csrf @method('PUT')
<div class="col-md-4"><label class="form-label">Minimum Order Amount</label><input type="number" step="0.01" min="0" name="minimum_order_amount" value="{{ old('minimum_order_amount', $settings['minimum_order_amount']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Delivery Charge</label><input type="number" step="0.01" min="0" name="delivery_charge" value="{{ old('delivery_charge', $settings['delivery_charge']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Today Cutoff Time</label><input type="time" name="today_delivery_cutoff_time" value="{{ old('today_delivery_cutoff_time', $settings['today_delivery_cutoff_time']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Max Delivery Days Ahead</label><input type="number" min="0" name="max_delivery_days_ahead" value="{{ old('max_delivery_days_ahead', $settings['max_delivery_days_ahead']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Default City</label><input name="default_city" value="{{ old('default_city', $settings['default_city']) }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">Default State</label><input name="default_state" value="{{ old('default_state', $settings['default_state']) }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Contact Mobile</label><input name="store_contact_mobile" value="{{ old('store_contact_mobile', $settings['store_contact_mobile']) }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">WhatsApp Number</label><input name="store_whatsapp_number" value="{{ old('store_whatsapp_number', $settings['store_whatsapp_number']) }}" class="form-control"></div>
@foreach (['cod_enabled'=>'COD Enabled','today_delivery_enabled'=>'Today Delivery Enabled','custom_delivery_date_enabled'=>'Custom Delivery Date Enabled','customer_invoice_enabled'=>'Customer Invoice Printing Enabled'] as $field => $label)
<div class="col-md-4"><div class="form-check mt-2"><input type="hidden" name="{{ $field }}" value="0"><input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(old($field, $settings[$field]))><label class="form-check-label" for="{{ $field }}">{{ $label }}</label></div></div>
@endforeach
<div class="col-12"><button class="btn btn-success">Save Settings</button></div>
</form>
</div></div>
@endsection
