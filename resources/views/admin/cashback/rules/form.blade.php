@csrf
<div class="card border-0 shadow-sm"><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input name="name" value="{{ old('name',$rule->name) }}" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Cashback %</label><input type="number" step="0.01" name="cashback_percent" value="{{ old('cashback_percent',$rule->cashback_percent ?? 5) }}" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Delay Days</label><input type="number" name="processing_delay_days" value="{{ old('processing_delay_days',$rule->processing_delay_days ?? 2) }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Monthly Threshold</label><input type="number" step="0.01" name="monthly_order_threshold" value="{{ old('monthly_order_threshold',$rule->monthly_order_threshold ?? 5000) }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Eligible Category %</label><input type="number" step="0.01" name="eligible_category_threshold_percent" value="{{ old('eligible_category_threshold_percent',$rule->eligible_category_threshold_percent ?? 50) }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Redemption Multiple</label><input type="number" step="0.01" name="redemption_multiple" value="{{ old('redemption_multiple',$rule->redemption_multiple ?? 500) }}" class="form-control" required></div>
<div class="col-md-3 form-check ms-3"><input type="checkbox" name="status" value="1" class="form-check-input" @checked(old('status',$rule->status ?? true))><label class="form-check-label">Active</label></div>
<div class="col-md-3 form-check ms-3"><input type="checkbox" name="is_default" value="1" class="form-check-input" @checked(old('is_default',$rule->is_default ?? false))><label class="form-check-label">Default</label></div>
</div><div class="card-footer bg-white"><button class="btn btn-success">Save</button></div></div>
