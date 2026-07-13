@extends('layouts.admin')

@section('title', 'Product Import')

@section('admin-content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Product Import</h1>
            <div class="text-muted">Upload CSV files to create or update products, variants, brand mapping, category mapping, and opening inventory.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.product-imports.template') }}" class="btn btn-outline-success">Blank Product Template</a>
            <a href="{{ route('admin.product-imports.export') }}" class="btn btn-success">Export Entire Catalog</a>
        </div>
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
                <div class="col-md-6">
                    <label for="csv_file" class="form-label">CSV File</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control @error('csv_file') is-invalid @enderror" accept=".csv,text/csv,text/plain">
                    @error('csv_file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('import')
                        <div class="text-danger small mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <label for="duplicate_action" class="form-label">Duplicate SKU Handling</label>
                    <select name="duplicate_action" id="duplicate_action" class="form-select @error('duplicate_action') is-invalid @enderror">
                        @foreach ($duplicateActions as $action)
                            <option value="{{ $action }}" @selected($selectedDuplicateAction === $action)>{{ str_replace('_', ' ', ucfirst($action)) }}</option>
                        @endforeach
                    </select>
                    @error('duplicate_action')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-success w-100">Preview Import</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-semibold">Export Filtered Catalog</div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.product-imports.export') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="brand_id" class="form-label">Brand</label>
                    <select name="brand_id" id="brand_id" class="form-select">
                        <option value="">All brands</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="supplier_id" class="form-label">Supplier</label>
                    <select name="supplier_id" id="supplier_id" class="form-select">
                        <option value="">All suppliers</option>
                        @foreach ($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="created_from" class="form-label">Date Created From</label>
                    <input type="date" name="created_from" id="created_from" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="created_to" class="form-label">Date Created To</label>
                    <input type="date" name="created_to" id="created_to" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="updated_from" class="form-label">Date Updated From</label>
                    <input type="date" name="updated_from" id="updated_from" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="updated_to" class="form-label">Date Updated To</label>
                    <input type="date" name="updated_to" id="updated_to" class="form-control">
                </div>
                <div class="col-12">
                    <button class="btn btn-success">Export Filtered Catalog</button>
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
                    <span class="text-muted small ms-2">{{ $preview['valid_rows'] }} valid, {{ $preview['error_rows'] }} with errors, {{ $preview['total_rows'] ?? count($preview['rows']) }} total</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if ($preview['has_errors'])
                        <a href="{{ route('admin.product-imports.error-report') }}" class="btn btn-outline-danger">Download CSV_Error_Report.csv</a>
                    @endif
                    <form method="POST" action="{{ route('admin.product-imports.import') }}">
                        @csrf
                        <button class="btn btn-success" @disabled($preview['has_errors'])>Import Validated CSV</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if (! empty($preview['summary']))
                    <div class="row g-2 mb-3">
                        @foreach ($preview['summary'] as $label => $value)
                            <div class="col-md-2">
                                <div class="border rounded bg-white p-2">
                                    <div class="small text-muted">{{ str_replace('_', ' ', ucfirst($label)) }}</div>
                                    <div class="fw-semibold">{{ $value }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if (($preview['total_rows'] ?? count($preview['rows'])) > ($preview['display_limit'] ?? 200))
                    <div class="alert alert-info small">Showing first {{ $preview['display_limit'] ?? 200 }} rows. Download the error report for the complete row-level validation output.</div>
                @endif
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

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white fw-semibold">Import History</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Filename</th>
                            <th>Rows</th>
                            <th>Success</th>
                            <th>Failure</th>
                            <th>Duration</th>
                            <th>Errors</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($histories as $history)
                            <tr>
                                <td>{{ $history->created_at->format('d M Y H:i') }}</td>
                                <td>{{ $history->user?->name ?? $history->user?->email ?? '-' }}</td>
                                <td>{{ $history->filename }}</td>
                                <td>{{ $history->rows_processed }}</td>
                                <td><span class="badge {{ $history->successful ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $history->successful ? 'Yes' : 'No' }}</span></td>
                                <td>{{ $history->rows_failed }}</td>
                                <td>{{ $history->duration_seconds }}s</td>
                                <td>{{ $history->error_count }}</td>
                                <td>
                                    @if ($history->error_report_path)
                                        <a href="{{ route('admin.product-imports.history.error-report', $history) }}" class="btn btn-sm btn-outline-danger">Errors</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No product imports yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($histories->hasPages())
            <div class="card-footer bg-white">{{ $histories->links() }}</div>
        @endif
    </div>
@endsection
