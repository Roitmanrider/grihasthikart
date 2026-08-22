<div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $customer->name ?? '') }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Mobile</label><input name="mobile" value="{{ old('mobile', $customer->mobile ?? '') }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="form-control"></div>
<div class="col-md-6">
    <label class="form-label">Assigned Store</label>
    <select name="assigned_store_id" class="form-select">
        <option value="">Default store</option>
        @foreach (($stores ?? collect()) as $store)
            <option value="{{ $store->id }}" @selected((string) old('assigned_store_id', $customer->assigned_store_id ?? '') === (string) $store->id)>{{ $store->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6"><label class="form-label">Monthly Cashback Threshold</label><input type="number" step="0.01" name="monthly_cashback_threshold" value="{{ old('monthly_cashback_threshold', $customer->monthly_cashback_threshold ?? '') }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Category Cashback Threshold %</label><input type="number" step="0.01" name="category_cashback_threshold_percent" value="{{ old('category_cashback_threshold_percent', $customer->category_cashback_threshold_percent ?? '') }}" class="form-control"></div>
<div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $customer->notes ?? '') }}</textarea></div>
@foreach (['status'=>'Active','is_premium'=>'Premium','cashback_enabled'=>'Cashback Enabled'] as $field => $label)
    <div class="col-md-4"><div class="form-check"><input type="hidden" name="{{ $field }}" value="0"><input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(old($field, $customer->{$field} ?? ($field === 'status')))><label class="form-check-label" for="{{ $field }}">{{ $label }}</label></div></div>
@endforeach
<div class="col-12"><hr><h2 class="h5 mb-1">Delivery Rules</h2><div class="text-muted small">Leave individual values blank to inherit the customer's Standard or Premium tier rule.</div></div>
<div class="col-12">
    <div class="form-check">
        <input type="hidden" name="custom_delivery_rules_enabled" value="0">
        <input class="form-check-input" type="checkbox" name="custom_delivery_rules_enabled" value="1" id="custom_delivery_rules_enabled" @checked(old('custom_delivery_rules_enabled', $customer->custom_delivery_rules_enabled ?? false))>
        <label class="form-check-label" for="custom_delivery_rules_enabled">Use Custom Delivery Rules</label>
    </div>
</div>
<div class="col-md-4">
    <label class="form-label">Minimum Order Amount</label>
    <input type="number" step="0.01" min="0" name="minimum_order_amount_override" value="{{ old('minimum_order_amount_override', $customer->minimum_order_amount_override ?? '') }}" class="form-control" placeholder="Inherit {{ ($customer->is_premium ?? false) ? 'Premium' : 'Standard' }}">
</div>
<div class="col-md-4">
    <label class="form-label">Delivery Charge</label>
    <input type="number" step="0.01" min="0" name="delivery_charge_override" value="{{ old('delivery_charge_override', $customer->delivery_charge_override ?? '') }}" class="form-control" placeholder="Inherit {{ ($customer->is_premium ?? false) ? 'Premium' : 'Standard' }}">
</div>
<div class="col-md-4">
    <label class="form-label">Free Delivery Threshold</label>
    <input type="number" step="0.01" min="0" name="free_delivery_threshold_override" value="{{ old('free_delivery_threshold_override', $customer->free_delivery_threshold_override ?? '') }}" class="form-control" placeholder="Inherit {{ ($customer->is_premium ?? false) ? 'Premium' : 'Standard' }}">
    <div class="form-text">0 means always free delivery.</div>
</div>
@if (isset($deliveryRule))
    <div class="col-12">
        <div class="alert alert-light border mb-0">
            <div class="fw-semibold mb-1">Effective Delivery Rule</div>
            <div class="row g-2 small">
                <div class="col-md-3">Customer Tier: <strong>{{ str($deliveryRule['customer_tier'])->headline() }}</strong></div>
                <div class="col-md-3">Minimum Order: <strong>Rs. {{ number_format((float) $deliveryRule['minimum_order_amount'], 2) }}</strong></div>
                <div class="col-md-3">Delivery Charge: <strong>{{ (float) $deliveryRule['delivery_charge_configured'] > 0 ? 'Rs. '.number_format((float) $deliveryRule['delivery_charge_configured'], 2) : 'Free' }}</strong></div>
                <div class="col-md-3">Free Threshold: <strong>{{ $deliveryRule['free_delivery_threshold'] === null ? 'None' : 'Rs. '.number_format((float) $deliveryRule['free_delivery_threshold'], 2) }}</strong></div>
            </div>
        </div>
    </div>
@endif
