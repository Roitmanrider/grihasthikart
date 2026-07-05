<div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name', $customer->name ?? '') }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Mobile</label><input name="mobile" value="{{ old('mobile', $customer->mobile ?? '') }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Monthly Cashback Threshold</label><input type="number" step="0.01" name="monthly_cashback_threshold" value="{{ old('monthly_cashback_threshold', $customer->monthly_cashback_threshold ?? '') }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Category Cashback Threshold %</label><input type="number" step="0.01" name="category_cashback_threshold_percent" value="{{ old('category_cashback_threshold_percent', $customer->category_cashback_threshold_percent ?? '') }}" class="form-control"></div>
<div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="3" class="form-control">{{ old('notes', $customer->notes ?? '') }}</textarea></div>
@foreach (['status'=>'Active','is_premium'=>'Premium','cashback_enabled'=>'Cashback Enabled'] as $field => $label)
    <div class="col-md-4"><div class="form-check"><input type="hidden" name="{{ $field }}" value="0"><input class="form-check-input" type="checkbox" name="{{ $field }}" value="1" id="{{ $field }}" @checked(old($field, $customer->{$field} ?? ($field === 'status')))><label class="form-check-label" for="{{ $field }}">{{ $label }}</label></div></div>
@endforeach
