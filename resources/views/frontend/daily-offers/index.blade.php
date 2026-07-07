@extends('layouts.frontend')

@section('title', 'Daily Offers - GrihasthiKart')
@section('description', 'Current Daily Offers from GrihasthiKart.')

@section('content')
    <section class="gk-section gk-daily-offers-page">
        <div class="container">
            <div class="gk-section-heading mb-3">
                <h1 class="h3 mb-0"><i class="fa-solid fa-stopwatch text-success me-2"></i> Daily Offers</h1>
                <a href="{{ route('home') }}">Back Home</a>
            </div>

            @if ($dailyOffers->isNotEmpty())
                <div class="gk-offer-grid">
                    @foreach ($dailyOffers as $dailyOffer)
                        @include('components.offer-card', ['dailyOffer' => $dailyOffer])
                    @endforeach
                </div>
            @else
                <div class="alert alert-light border mb-0">Daily offers coming soon.</div>
            @endif
        </div>
    </section>
@endsection
