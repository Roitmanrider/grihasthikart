@extends('layouts.frontend')

@section('title', 'Contact Us - GrihasthiKart')
@section('description', 'Contact GrihasthiKart customer support.')

@section('content')
    <section class="py-5 gk-content-page">
        <div class="container">
            <div class="mb-4">
                <h1 class="h2 mb-2">Contact Us</h1>
                <p class="text-muted mb-0">Reach out for grocery orders, delivery, payments, returns, coupons, cashback, or account support.</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">{{ $business['name'] }}</h2>

                            @if ($phoneUrl)
                                <p><i class="fa-solid fa-phone text-success me-2"></i><a href="{{ $phoneUrl }}">{{ $business['support_phone'] }}</a></p>
                            @endif

                            @if ($whatsappUrl)
                                <p><i class="fa-brands fa-whatsapp text-success me-2"></i><a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp Support</a></p>
                            @endif

                            @if ($business['support_email'])
                                <p><i class="fa-regular fa-envelope text-success me-2"></i><a href="mailto:{{ $business['support_email'] }}">{{ $business['support_email'] }}</a></p>
                            @endif

                            @if ($business['address'] || $business['city'] || $business['state'] || $business['pincode'])
                                <p class="mb-2"><i class="fa-solid fa-location-dot text-success me-2"></i>{{ collect([$business['address'], $business['city'], $business['state'], $business['pincode']])->filter()->join(', ') }}</p>
                            @endif

                            @if ($business['business_hours'])
                                <p><i class="fa-regular fa-clock text-success me-2"></i>{{ $business['business_hours'] }}</p>
                            @endif

                            @if ($business['google_maps_url'])
                                <a href="{{ $business['google_maps_url'] }}" target="_blank" rel="noopener" class="btn btn-outline-success">Open Google Maps</a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">Send a Message</h2>

                            <form method="POST" action="{{ route('contact-messages.store') }}" class="row g-3">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror">
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mobile</label>
                                    <input name="mobile" value="{{ old('mobile') }}" class="form-control @error('mobile') is-invalid @enderror">
                                    @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror">
                                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Subject</label>
                                    <input name="subject" value="{{ old('subject') }}" class="form-control @error('subject') is-invalid @enderror">
                                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message</label>
                                    <textarea name="message" rows="5" class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-success">Submit Message</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
