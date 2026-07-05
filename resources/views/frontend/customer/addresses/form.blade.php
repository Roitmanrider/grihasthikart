<div class="col-md-6"><label class="form-label">Label</label><input name="label" value="{{ old('label', $address->label ?? '') }}" class="form-control"></div>
<div class="col-md-6"><label class="form-label">Recipient</label><input name="recipient_name" value="{{ old('recipient_name', $address->recipient_name ?? $customer->name ?? '') }}" class="form-control" required></div>
<div class="col-md-6"><label class="form-label">Mobile</label><input name="mobile" value="{{ old('mobile', $address->mobile ?? $customer->mobile ?? '') }}" class="form-control" required></div>
<div class="col-12"><label class="form-label">Address Line 1</label><input name="address_line1" value="{{ old('address_line1', $address->address_line1 ?? '') }}" class="form-control" required></div>
<div class="col-12"><label class="form-label">Address Line 2</label><input name="address_line2" value="{{ old('address_line2', $address->address_line2 ?? '') }}" class="form-control"></div>
<div class="col-md-4"><label class="form-label">City</label><input name="city" value="{{ old('city', $address->city ?? '') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">State</label><input name="state" value="{{ old('state', $address->state ?? '') }}" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Pincode</label><input name="pincode" value="{{ old('pincode', $address->pincode ?? '') }}" class="form-control" required></div>
<div class="col-12"><label class="form-label">Landmark</label><input name="landmark" value="{{ old('landmark', $address->landmark ?? '') }}" class="form-control"></div>
<div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default" @checked(old('is_default', $address->is_default ?? false))><label class="form-check-label" for="is_default">Default address</label></div></div>
