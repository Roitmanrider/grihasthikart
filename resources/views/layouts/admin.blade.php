@extends('layouts.app')

@section('content')

<div class="admin-wrapper">

    @include('partials.sidebar')

    <div class="admin-content">

        @include('partials.topbar')

        @yield('admin-content')

    </div>

</div>

@endsection
