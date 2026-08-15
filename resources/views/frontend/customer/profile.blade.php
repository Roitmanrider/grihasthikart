@extends('layouts.frontend')

@section('title', 'My Profile')

@section('content')
<section class="py-5">
    <div class="container">
        @include('frontend.customer.account-nav')

        <div class="mb-4">
            <h1 class="h3 mb-1">My Profile</h1>
            <div class="text-muted">Mobile number and account entitlements are managed securely.</div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('customer.profile.update') }}" class="row g-3">
                    @csrf
                    @method('PATCH')
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input name="email" type="email" class="form-control" value="{{ old('email', $customer->email) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile</label>
                        <input class="form-control" value="{{ $customer->mobile }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Type</label>
                        <input class="form-control" value="{{ $customer->is_premium ? 'Premium' : 'Standard' }}" readonly>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
