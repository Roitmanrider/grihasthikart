@extends('layouts.frontend')

@section('title', $page['title'].' - GrihasthiKart')
@section('description', $page['description'])

@section('content')
    <section class="py-5 gk-content-page">
        <div class="container">
            <div class="mb-4">
                <h1 class="h2 mb-2">{{ $page['title'] }}</h1>
                <p class="text-muted mb-0">{{ $page['description'] }}</p>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            @foreach ($page['sections'] as [$heading, $body])
                                <section class="mb-4">
                                    <h2 class="h5">{{ $heading }}</h2>
                                    <p class="text-muted mb-0">{{ $body }}</p>
                                </section>
                            @endforeach
                            <p class="small text-muted mb-0">Last updated: {{ now()->format('d M Y') }}. These policies may be updated as operations evolve.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5">Need Help?</h2>
                            <p class="text-muted">Contact customer support for order, delivery, return, coupon, or cashback help.</p>
                            <a href="{{ route('pages.contact') }}" class="btn btn-success w-100">Contact Support</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
