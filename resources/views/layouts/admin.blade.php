@extends('layouts.app')

@section('content')

<div class="d-flex min-vh-100 bg-light">

    @include('partials.sidebar')

    <div class="flex-grow-1">

        @include('partials.topbar')

        <main class="container-fluid py-4">

            <div class="d-flex justify-content-end mb-3">
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Logout</button>
                </form>
            </div>

            @include('partials.flash')

            @yield('admin-content')

        </main>

    </div>

</div>

@endsection
