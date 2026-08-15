@extends('layouts.admin')

@section('title', 'Edit Partner')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Partner</h1>
            <div class="text-muted">{{ $partner->name }}</div>
        </div>
        <a href="{{ route('admin.homepage.partners.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @include('admin.homepage.partners.form', ['action' => route('admin.homepage.partners.update', $partner), 'method' => 'PUT'])
@endsection
