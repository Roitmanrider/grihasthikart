@extends('layouts.admin')

@section('title', 'Homepage Sections')

@section('admin-content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Homepage Sections</h1>
            <div class="text-muted">Control storefront section visibility, order, titles, and content source.</div>
        </div>
        <a href="{{ route('home') }}" class="btn btn-outline-success" target="_blank" rel="noopener noreferrer">View Homepage</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th>Source</th>
                        <th>Limit</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sections as $section)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $section->title }}</div>
                                <div class="small text-muted">{{ $section->section_key }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $section->enabled ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $section->enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                            </td>
                            <td>{{ $section->sort_order }}</td>
                            <td>{{ str($section->section_type)->replace('_', ' ')->title() }}</td>
                            <td>{{ $section->desktop_item_limit }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.homepage.sections.edit', $section->section_key) }}" class="btn btn-sm btn-outline-success">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
