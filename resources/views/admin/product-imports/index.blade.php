@extends('layouts.admin')

@section('title', 'Product Import')

@section('admin-content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Product Import</h1>
            <div class="text-muted">Upload CSV files to create or update products, variants, brand mapping, category mapping, and opening inventory.</div>
        </div>
        <a href="{{ route('admin.product-imports.template') }}" class="btn btn-outline-success">Download Template</a>
    </div>

    @if (session('import_summary'))
        <div class="alert alert-success">
            <div class="fw-semibold mb-2">Import Summary</div>
            <div class="row g-2">
                @foreach (session('import_summary') as $label => $value)
                    <div class="col-md-3">
                        <div class="border rounded bg-white p-2">
                            <div class="small text-muted">{{ str_replace('_', ' ', ucfirst($label)) }}</div>
                            <div class="h5 mb-0">{{ $value }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Upload CSV</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.product-imports.preview') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-8">
                    <label for="csv_file" class="form-label">CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".csv,text/csv,text/plain">
                    @error('csv_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('import')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-success w-100">Preview Import</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">CSV Format</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Column</th>
                            <th>Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($headers as $header)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><code>{{ $header }}</code></td>
                                <td>
                                    @if (in_array($header, $requiredHeaders, true))
                                        <span class="badge text-bg-danger">Required</span>
                                    @else
                                        <span class="badge text-bg-light">Optional</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-3">
                sub_subcategory may be blank when the product belongs directly under a subcategory. Image columns only reference files that already exist under uploads/products.
            </div>
        </div>
    </div>

    @if ($preview)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <span class="fw-semibold">Preview</span>
                    <span class="text-muted small ms-2">{{ $preview['valid_rows'] }} valid, {{ $preview['error_rows'] }} with errors</span>
                </div>
                <form method="POST" action="{{ route('admin.product-imports.import') }}">
                    @csrf
                    <button class="btn btn-success" @disabled($preview['has_errors'])>Import Validated CSV</button>
                </form>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Category Path</th>
                                <th>Variant</th>
                                <th>SKU</th>
                                <th>Status</th>
                                <th>Messages</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($preview['rows'] as $row)
                                <tr class="{{ $row['errors'] ? 'table-danger' : '' }}">
                                    <td>{{ $row['row_number'] }}</td>
                                    <td>{{ $row['data']['product_name'] }}</td>
                                    <td>{{ $row['data']['brand_name'] ?: '-' }}</td>
                                    <td>
                                        {{ collect([$row['data']['category'], $row['data']['subcategory'], $row['data']['sub_subcategory']])->filter()->implode(' > ') }}
                                    </td>
                                    <td>{{ $row['data']['variant_name'] }}</td>
                                    <td><code>{{ $row['data']['sku'] }}</code></td>
                                    <td><span class="badge text-bg-secondary">{{ $row['status'] }}</span></td>
                                    <td>
                                        @foreach ($row['errors'] as $error)
                                            <div class="text-danger small">{{ $error }}</div>
                                        @endforeach
                                        @foreach ($row['warnings'] as $warning)
                                            <div class="text-muted small">{{ $warning }}</div>
                                        @endforeach
                                        @if (! $row['errors'] && ! $row['warnings'])
                                            <span class="text-success small">Ready</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
@endsection
