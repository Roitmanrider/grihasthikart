<form method="GET" class="card border-0 shadow-sm mb-4">
    <div class="card-body row g-3">
        <div class="col-md-2"><label class="form-label">From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div class="col-md-2"><label class="form-label">Order</label><select name="order_status" class="form-select"><option value="">Default active</option>@foreach(\App\Models\Order::STATUSES as $status)<option value="{{ $status }}" @selected(request('order_status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Payment Status</label><select name="payment_status" class="form-select"><option value="">Default valid</option>@foreach(\App\Models\Order::PAYMENT_STATUSES as $status)<option value="{{ $status }}" @selected(request('payment_status') === $status)>{{ str($status)->headline() }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">Method</label><select name="payment_method" class="form-select"><option value="">All</option>@foreach(\App\Models\Order::PAYMENT_METHODS as $method)<option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ strtoupper($method) }}</option>@endforeach</select></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-success w-100">Filter</button></div>
    </div>
</form>
