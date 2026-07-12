@csrf

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Supplier Information</h2>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $supplier->name ?? '') }}" class="form-control @error('name') is-invalid @enderror" required>
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person ?? '') }}" class="form-control @error('contact_person') is-invalid @enderror">
                        @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile</label>
                        <input type="text" name="mobile" value="{{ old('mobile', $supplier->mobile ?? '') }}" class="form-control @error('mobile') is-invalid @enderror">
                        @error('mobile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" value="{{ old('email', $supplier->email ?? '') }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="gstin" value="{{ old('gstin', $supplier->gstin ?? '') }}" class="form-control @error('gstin') is-invalid @enderror">
                        @error('gstin') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Opening Balance</label>
                        <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', $supplier->opening_balance ?? 0) }}" class="form-control @error('opening_balance') is-invalid @enderror">
                        @error('opening_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Address</label>
                        <textarea name="address" rows="3" class="form-control @error('address') is-invalid @enderror">{{ old('address', $supplier->address ?? '') }}</textarea>
                        @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" value="{{ old('city', $supplier->city ?? '') }}" class="form-control @error('city') is-invalid @enderror">
                        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" name="state" value="{{ old('state', $supplier->state ?? '') }}" class="form-control @error('state') is-invalid @enderror">
                        @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" value="{{ old('pincode', $supplier->pincode ?? '') }}" class="form-control @error('pincode') is-invalid @enderror">
                        @error('pincode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Status</h2>
            </div>
            <div class="card-body">
                <select name="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="active" @selected(old('status', $supplier->status ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $supplier->status ?? 'active') === 'inactive')>Inactive</option>
                </select>
                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-grid gap-2">
            <button class="btn btn-success">Save Supplier</button>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>
