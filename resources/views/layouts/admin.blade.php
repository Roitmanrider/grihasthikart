@extends('layouts.app')

@section('content')

<div class="d-flex min-vh-100 bg-light">

    @include('partials.sidebar')

    <div class="flex-grow-1">

        @include('partials.topbar')

        <main class="container-fluid py-4">

            @include('partials.flash')

            @yield('admin-content')

        </main>

    </div>

</div>

@endsection
