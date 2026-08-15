@extends('layouts.admin')

@section('title', 'Edit Banner')

@section('admin-content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Edit Banner</h1>
            <div class="text-muted">{{ $banner->title ?: 'Untitled banner' }}</div>
        </div>
        <a href="{{ route('admin.homepage.banners.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    @include('admin.homepage.banners.form', ['action' => route('admin.homepage.banners.update', $banner), 'method' => 'PUT'])
@endsection
