@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Coupon Details</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" value="{{ old('code', $coupon->code) }}" class="form-control" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $coupon->name) }}" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $coupon->description) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Type</label>
                    <select name="discount_type" class="form-select" required>
                        @foreach (\App\Models\Coupon::DISCOUNT_TYPES as $type)
                            <option value="{{ $type }}" @selected(old('discount_type', $coupon->discount_type ?: 'fixed') === $type)>{{ str($type)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Value</label>
                    <input type="number" step="0.01" min="0.01" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Max Discount</label>
                    <input type="number" step="0.01" min="0" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Minimum Order</label>
                    <input type="number" step="0.01" min="0" name="minimum_order_amount" value="{{ old('minimum_order_amount', $coupon->minimum_order_amount ?? 0) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Starts At</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expires At</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d\TH:i')) }}" class="form-control">
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white fw-semibold">Usage Limits</div>
            <div class="card-body row g-3">
                <div class="col-md-4"><label class="form-label">Total Limit</label><input type="number" min="1" name="usage_limit_total" value="{{ old('usage_limit_total', $coupon->usage_limit_total) }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Per Customer</label><input type="number" min="1" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer', $coupon->usage_limit_per_customer) }}" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">Per Session</label><input type="number" min="1" name="usage_limit_per_session" value="{{ old('usage_limit_per_session', $coupon->usage_limit_per_session) }}" class="form-control"></div>
                <div class="col-md-6">
                    <label class="form-label">Customer Specific</label>
                    <select name="customer_id" class="form-select">
                        <option value="">Public coupon</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id', $coupon->customer_id) === (string) $customer->id)>{{ $customer->name }} / {{ $customer->mobile }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Source</label>
                    <select name="source" class="form-select">
                        @foreach (\App\Models\Coupon::SOURCES as $source)
                            <option value="{{ $source }}" @selected(old('source', $coupon->source ?: 'admin') === $source)>{{ str($source)->headline() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Status</div>
            <div class="card-body">
                <input type="hidden" name="status" value="0">
                <input type="hidden" name="is_cashback_coupon" value="0">
                <div class="form-check form-switch mb-3">
                    <input type="checkbox" name="status" value="1" class="form-check-input" id="status" @checked(old('status', $coupon->status ?? true))>
                    <label for="status" class="form-check-label">Active</label>
                </div>
                <div class="form-check form-switch">
                    <input type="checkbox" name="is_cashback_coupon" value="1" class="form-check-input" id="is_cashback_coupon" @checked(old('is_cashback_coupon', $coupon->is_cashback_coupon ?? false))>
                    <label for="is_cashback_coupon" class="form-check-label">Cashback coupon placeholder</label>
                </div>
            </div>
        </div>
        <div class="d-grid gap-2 mt-4">
            <button class="btn btn-success">Save Coupon</button>
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
