@extends('layouts.admin')

@section('title', 'Create Partner')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Create Partner</h1>
            <div class="text-muted">Add a homepage partner card.</div>
        </div>
        <a href="{{ route('admin.homepage.partners.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @include('admin.homepage.partners.form', ['action' => route('admin.homepage.partners.store'), 'method' => 'POST'])
@endsection
