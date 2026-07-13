<?php

namespace App\Domains\Catalog\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductImportHistory;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ProductCatalogImportExportService
{
    public const DUPLICATE_SKIP = 'skip_existing';

    public const DUPLICATE_UPDATE = 'update_existing';

    public const DUPLICATE_ABORT = 'abort';

    public const DUPLICATE_ACTIONS = [
        self::DUPLICATE_SKIP,
        self::DUPLICATE_UPDATE,
        self::DUPLICATE_ABORT,
    ];

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly ProductImportService $importService
    ) {}

    public function headers(): array
    {
        return ProductImportService::HEADERS;
    }

    public function requiredHeaders(): array
    {
        return [
            'product_name',
            'brand_name',
            'category',
            'subcategory',
            'variant_name',
            'sku',
            'mrp',
            'selling_price',
            'gst_rate',
            'weight',
        ];
    }

    public function template(): string
    {
        return $this->csv([], $this->headers());
    }

    public function preview(UploadedFile $file, string $duplicateAction): array
    {
        $this->validateDuplicateAction($duplicateAction);
        $startedAt = microtime(true);
        $rows = [];
        $visibleRows = [];
        $errors = [];
        $warnings = [];

        foreach ($this->readUploadedCsv($file) as $rowNumber => $row) {
            $rows[] = $row;
            $result = $this->validateRow($row, $rowNumber, $duplicateAction);
            $errors = array_merge($errors, $result['errors']);
            $warnings = array_merge($warnings, $result['warnings']);

            if (count($visibleRows) < 200) {
                $visibleRows[] = [
                    'row_number' => $rowNumber,
                    'data' => $row,
                    'status' => $result['status'],
                    'errors' => array_column($result['errors'], 'reason'),
                    'warnings' => array_column($result['warnings'], 'reason'),
                ];
            }
        }

        if ($rows === []) {
            throw new InvalidArgumentException('CSV file does not contain import rows.');
        }

        $rowPath = $this->storeRows($rows);
        $errorReportPath = $this->storeErrorReport($errors);
        $failedRows = collect($errors)->pluck('row_number')->unique()->count();

        return [
            'rows' => $visibleRows,
            'row_path' => $rowPath,
            'error_report_path' => $errorReportPath,
            'valid_rows' => count($rows) - $failedRows,
            'error_rows' => $failedRows,
            'total_rows' => count($rows),
            'display_limit' => 200,
            'has_errors' => $errors !== [],
            'errors' => $errors,
            'warnings' => $warnings,
            'summary' => [
                'rows_processed' => count($rows),
                'rows_failed' => $failedRows,
                'errors' => count($errors),
                'warnings' => count($warnings),
                'time_taken' => $this->duration($startedAt),
            ],
        ];
    }

    public function import(string $rowPath, string $duplicateAction, ?int $userId, ?string $filename): array
    {
        $this->validateDuplicateAction($duplicateAction);

        if (! Storage::disk('local')->exists($rowPath)) {
            throw new InvalidArgumentException('Upload and preview a valid CSV before importing.');
        }

        $startedAt = microtime(true);
        $rows = $this->rowsFromStoredCsv($rowPath);
        $validationErrors = [];

        foreach ($rows as $index => $row) {
            $validationErrors = array_merge($validationErrors, $this->validateRow($row, $index + 2, $duplicateAction)['errors']);
        }

        if ($validationErrors !== []) {
            $reportPath = $this->storeErrorReport($validationErrors);
            $summary = $this->summaryForFailure($rows, $validationErrors, $startedAt);
            $this->recordHistory($userId, $filename, $duplicateAction, false, $summary, $reportPath);

            throw new InvalidArgumentException('Fix CSV validation errors before importing.');
        }

        try {
            $summary = DB::transaction(function () use ($rows, $duplicateAction, $startedAt) {
                $summary = $this->emptyImportSummary();

                foreach (array_chunk($rows, 500) as $chunk) {
                    foreach ($chunk as $row) {
                        if ($duplicateAction === self::DUPLICATE_SKIP && ProductVariant::query()->where('sku', $row['sku'])->exists()) {
                            $summary['rows_processed']++;
                            $summary['rows_skipped']++;

                            continue;
                        }

                        $before = $this->counts();
                        $this->importService->import([$row]);
                        $after = $this->counts();

                        $summary['rows_processed']++;
                        $summary['products_created'] += max(0, $after['products'] - $before['products']);
                        $summary['variants_created'] += max(0, $after['variants'] - $before['variants']);
                        $summary['images_attached'] += max(0, $after['images'] - $before['images']);
                        $summary['products_updated'] += $after['products'] === $before['products'] ? 1 : 0;
                        $summary['variants_updated'] += $after['variants'] === $before['variants'] ? 1 : 0;
                    }
                }

                $summary['time_taken'] = $this->duration($startedAt);

                return $summary;
            });

            $this->recordHistory($userId, $filename, $duplicateAction, true, $summary, null);

            return $summary;
        } catch (InvalidArgumentException|RuntimeException $exception) {
            $reportPath = $this->storeErrorReport([[
                'row_number' => '',
                'sku' => '',
                'column' => 'import',
                'invalid_value' => '',
                'reason' => $exception->getMessage(),
            ]]);
            $summary = $this->emptyImportSummary();
            $summary['rows_processed'] = count($rows);
            $summary['rows_failed'] = count($rows);
            $summary['errors'] = 1;
            $summary['time_taken'] = $this->duration($startedAt);
            $this->recordHistory($userId, $filename, $duplicateAction, false, $summary, $reportPath);

            throw $exception;
        }
    }

    public function errorReport(?string $path): string
    {
        if ($path && Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->get($path);
        }

        return $this->csv([], ['Row Number', 'SKU', 'Column', 'Invalid Value', 'Reason']);
    }

    public function export(array $filters): string
    {
        $handle = fopen('php://temp', 'rb+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV export stream.');
        }

        fputcsv($handle, $this->headers());

        $this->exportQuery($filters)->chunkById(500, function ($products) use ($handle) {
            foreach ($products as $product) {
                $path = $this->categoryPath($product);
                $productImage = $product->images->first()?->path ?? '';

                foreach ($product->variants as $variant) {
                    fputcsv($handle, [
                        $product->name,
                        $product->brand?->name ?? '',
                        $path[0] ?? '',
                        $path[1] ?? '',
                        $path[2] ?? '',
                        $variant->variant_name,
                        $variant->sku,
                        $variant->mrp,
                        $variant->selling_price,
                        $variant->purchase_price,
                        $product->gst_rate,
                        $product->hsn_code,
                        $variant->barcode,
                        $variant->weight,
                        $variant->unit,
                        '',
                        '',
                        '',
                        '',
                        $product->is_featured ? '1' : '0',
                        $product->is_trending ? '1' : '0',
                        $product->is_popular ? '1' : '0',
                        $product->is_new_arrival ? '1' : '0',
                        $product->status && $variant->status ? '1' : '0',
                        $productImage,
                        $variant->images->first()?->path ?? '',
                        $product->short_description,
                        $product->description,
                        $product->meta_title,
                        $product->meta_description,
                        $product->meta_keywords,
                    ]);
                }
            }
        });

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    public function histories()
    {
        return ProductImportHistory::query()
            ->with('user')
            ->latest()
            ->paginate(10);
    }

    private function validateRow(array $row, int $rowNumber, string $duplicateAction): array
    {
        $errors = [];
        $warnings = [];

        foreach ($this->requiredHeaders() as $field) {
            if ($this->blank($row[$field] ?? null)) {
                $errors[] = $this->error($rowNumber, $row, $field, $row[$field] ?? '', str_replace('_', ' ', $field).' is required.');
            }
        }

        $this->validateCategoryPath($row, $rowNumber, $errors);
        $this->validateSoftDeletedBrand($row, $rowNumber, $errors);
        $this->validateDuplicateSkuAndBarcode($row, $rowNumber, $duplicateAction, $errors, $warnings);
        $this->validateNumbers($row, $rowNumber, $errors);
        $this->validateImages($row, $rowNumber, $errors);

        if ($this->blank($row['sub_subcategory'] ?? null)) {
            $warnings[] = $this->error($rowNumber, $row, 'sub_subcategory', '', 'sub_subcategory is blank; product will be assigned to the deepest provided category.');
        }

        $variant = $this->activeVariantBySku($row['sku'] ?? '');
        $product = $this->activeProductByName($row['product_name'] ?? '');

        return [
            'status' => match (true) {
                $variant !== null && $duplicateAction === self::DUPLICATE_SKIP => 'skip existing',
                $variant !== null => 'update variant',
                $product !== null => 'create variant',
                default => 'create product and variant',
            },
            'errors' => collect($errors)->unique(fn ($error) => implode('|', $error))->values()->all(),
            'warnings' => collect($warnings)->unique(fn ($warning) => implode('|', $warning))->values()->all(),
        ];
    }

    private function validateCategoryPath(array $row, int $rowNumber, array &$errors): void
    {
        $category = $this->category($row['category'] ?? '', null, true);

        if ($category?->trashed()) {
            $errors[] = $this->error($rowNumber, $row, 'category', $row['category'] ?? '', 'Category exists but is soft-deleted. Restore it before importing.');

            return;
        }

        if (! $category) {
            $errors[] = $this->error($rowNumber, $row, 'category', $row['category'] ?? '', 'Category was not found.');

            return;
        }

        if (! $this->blank($row['subcategory'] ?? null)) {
            $subcategory = $this->category($row['subcategory'], $category->id, true);

            if ($subcategory?->trashed()) {
                $errors[] = $this->error($rowNumber, $row, 'subcategory', $row['subcategory'], 'Subcategory exists but is soft-deleted. Restore it before importing.');
            } elseif (! $subcategory) {
                $errors[] = $this->error($rowNumber, $row, 'subcategory', $row['subcategory'], 'Subcategory was not found under the selected category.');
            }

            if (! $this->blank($row['sub_subcategory'] ?? null) && $subcategory && ! $subcategory->trashed()) {
                $subSubcategory = $this->category($row['sub_subcategory'], $subcategory->id, true);

                if ($subSubcategory?->trashed()) {
                    $errors[] = $this->error($rowNumber, $row, 'sub_subcategory', $row['sub_subcategory'], 'Sub-subcategory exists but is soft-deleted. Restore it before importing.');
                } elseif (! $subSubcategory) {
                    $errors[] = $this->error($rowNumber, $row, 'sub_subcategory', $row['sub_subcategory'], 'Sub-subcategory was not found under the selected subcategory.');
                }
            }
        } elseif (! $this->blank($row['sub_subcategory'] ?? null)) {
            $errors[] = $this->error($rowNumber, $row, 'sub_subcategory', $row['sub_subcategory'], 'Sub-subcategory requires a matching subcategory.');
        }
    }

    private function validateSoftDeletedBrand(array $row, int $rowNumber, array &$errors): void
    {
        if ($this->blank($row['brand_name'] ?? null)) {
            return;
        }

        $brand = Brand::withTrashed()
            ->where('name', $row['brand_name'])
            ->orWhere('slug', Str::slug($row['brand_name']))
            ->first();

        if ($brand?->trashed()) {
            $errors[] = $this->error($rowNumber, $row, 'brand_name', $row['brand_name'], 'Brand exists but is soft-deleted. Restore it before importing.');
        }
    }

    private function validateDuplicateSkuAndBarcode(array $row, int $rowNumber, string $duplicateAction, array &$errors, array &$warnings): void
    {
        $product = $this->activeProductByName($row['product_name'] ?? '');
        $variant = ProductVariant::withTrashed()->where('sku', $row['sku'] ?? '')->first();

        if ($variant?->trashed()) {
            $errors[] = $this->error($rowNumber, $row, 'sku', $row['sku'] ?? '', 'SKU exists on a soft-deleted variant. Restore it before importing.');
        } elseif ($variant && ($product === null || $variant->product_id !== $product->id)) {
            $errors[] = $this->error($rowNumber, $row, 'sku', $row['sku'] ?? '', 'SKU already belongs to a different product.');
        } elseif ($variant && $duplicateAction === self::DUPLICATE_ABORT) {
            $errors[] = $this->error($rowNumber, $row, 'sku', $row['sku'] ?? '', 'SKU already exists and duplicate handling is set to abort.');
        } elseif ($variant && $duplicateAction === self::DUPLICATE_SKIP) {
            $warnings[] = $this->error($rowNumber, $row, 'sku', $row['sku'] ?? '', 'SKU already exists and will be skipped.');
        }

        if (! $this->blank($row['barcode'] ?? null)) {
            $barcodeVariant = ProductVariant::withTrashed()->where('barcode', $row['barcode'])->first();

            if ($barcodeVariant?->trashed()) {
                $errors[] = $this->error($rowNumber, $row, 'barcode', $row['barcode'], 'Barcode exists on a soft-deleted variant. Restore it before importing.');
            } elseif ($barcodeVariant && (! $variant || $barcodeVariant->id !== $variant->id)) {
                $errors[] = $this->error($rowNumber, $row, 'barcode', $row['barcode'], 'Barcode already belongs to another product variant.');
            }
        }

        $softDeletedProduct = Product::withTrashed()
            ->where('name', $row['product_name'] ?? '')
            ->orWhere('slug', Str::slug($row['product_name'] ?? ''))
            ->first();

        if ($softDeletedProduct?->trashed()) {
            $errors[] = $this->error($rowNumber, $row, 'product_name', $row['product_name'] ?? '', 'Product exists but is soft-deleted. Restore it before importing.');
        }
    }

    private function validateNumbers(array $row, int $rowNumber, array &$errors): void
    {
        foreach (['mrp', 'selling_price', 'purchase_price', 'gst_rate', 'weight', 'opening_stock', 'low_stock_threshold', 'reorder_level', 'target_stock_level'] as $field) {
            if (! $this->blank($row[$field] ?? null) && ! is_numeric($row[$field])) {
                $errors[] = $this->error($rowNumber, $row, $field, $row[$field], str_replace('_', ' ', $field).' must be numeric.');
            }
        }

        foreach (['mrp', 'selling_price', 'purchase_price', 'weight', 'opening_stock', 'low_stock_threshold', 'reorder_level', 'target_stock_level'] as $field) {
            if (! $this->blank($row[$field] ?? null) && is_numeric($row[$field]) && (float) $row[$field] < 0) {
                $errors[] = $this->error($rowNumber, $row, $field, $row[$field], str_replace('_', ' ', $field).' cannot be negative.');
            }
        }

        if (is_numeric($row['mrp'] ?? null) && is_numeric($row['selling_price'] ?? null) && (float) $row['selling_price'] > (float) $row['mrp']) {
            $errors[] = $this->error($rowNumber, $row, 'selling_price', $row['selling_price'], 'Selling price cannot be greater than MRP.');
        }

        if (is_numeric($row['gst_rate'] ?? null) && ((float) $row['gst_rate'] < 0 || (float) $row['gst_rate'] > 28)) {
            $errors[] = $this->error($rowNumber, $row, 'gst_rate', $row['gst_rate'], 'GST must be between 0 and 28.');
        }

        if (
            is_numeric($row['opening_stock'] ?? null)
            && (float) $row['opening_stock'] > 0
            && ! StockLocation::query()->active()->where('is_default', true)->exists()
        ) {
            $errors[] = $this->error($rowNumber, $row, 'opening_stock', $row['opening_stock'], 'Default stock location is required before importing opening stock.');
        }
    }

    private function validateImages(array $row, int $rowNumber, array &$errors): void
    {
        foreach (['product_image' => false, 'variant_image' => true] as $field => $isVariant) {
            if ($this->blank($row[$field] ?? null)) {
                continue;
            }

            $extension = Str::lower(pathinfo($row[$field], PATHINFO_EXTENSION));

            if (! in_array($extension, self::IMAGE_EXTENSIONS, true)) {
                $errors[] = $this->error($rowNumber, $row, $field, $row[$field], str_replace('_', ' ', $field).' must be jpg, jpeg, png, or webp.');

                continue;
            }

            if ($this->imagePath($row[$field], $row, $isVariant) === null) {
                $errors[] = $this->error($rowNumber, $row, $field, $row[$field], str_replace('_', ' ', $field).' was not found under uploads/products.');
            }
        }
    }

    private function readUploadedCsv(UploadedFile $file): iterable
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('Unable to read uploaded CSV file.');
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                throw new InvalidArgumentException('CSV file is empty.');
            }

            $headers = array_map(fn ($header) => trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header) ?? ''), $headers);

            if ($headers !== $this->headers()) {
                throw new InvalidArgumentException('CSV headers must exactly match the Product Import template.');
            }

            $rowNumber = 1;

            while (($line = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (collect($line)->every(fn ($value) => $this->blank($value))) {
                    continue;
                }

                yield $rowNumber => $this->normalizeRow($line);
            }
        } finally {
            fclose($handle);
        }
    }

    private function exportQuery(array $filters)
    {
        return Product::query()
            ->with(['brand', 'categories.parent.parent', 'images', 'variants.images'])
            ->when(($filters['brand_id'] ?? '') !== '', fn ($query) => $query->where('brand_id', (int) $filters['brand_id']))
            ->when(($filters['category_id'] ?? '') !== '', fn ($query) => $query->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereKey((int) $filters['category_id'])))
            ->when(($filters['status'] ?? '') !== '', fn ($query) => $query->where('status', (bool) $filters['status']))
            ->when(($filters['created_from'] ?? '') !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['created_from']))
            ->when(($filters['created_to'] ?? '') !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['created_to']))
            ->when(($filters['updated_from'] ?? '') !== '', fn ($query) => $query->whereDate('updated_at', '>=', $filters['updated_from']))
            ->when(($filters['updated_to'] ?? '') !== '', fn ($query) => $query->whereDate('updated_at', '<=', $filters['updated_to']))
            ->when(($filters['supplier_id'] ?? '') !== '', function ($query) use ($filters) {
                $query->whereHas('variants', function ($variantQuery) use ($filters) {
                    $variantQuery->whereHas('purchaseEntryItems.purchaseEntry', fn ($purchaseQuery) => $purchaseQuery->where('supplier_id', (int) $filters['supplier_id']));
                });
            })
            ->orderBy('id');
    }

    private function categoryPath(Product $product): array
    {
        $category = $product->categories->firstWhere('pivot.is_primary', true) ?? $product->categories->first();
        $path = [];

        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }

        return $path;
    }

    private function category(?string $value, ?int $parentId, bool $withTrashed = false): ?Category
    {
        if ($this->blank($value)) {
            return null;
        }

        return Category::query()
            ->when($withTrashed, fn ($query) => $query->withTrashed())
            ->where('parent_id', $parentId)
            ->where(fn ($query) => $query->where('name', $value)->orWhere('slug', Str::slug($value)))
            ->first();
    }

    private function activeProductByName(?string $name): ?Product
    {
        if ($this->blank($name)) {
            return null;
        }

        return Product::query()
            ->where('name', $name)
            ->orWhere('slug', Str::slug($name))
            ->first();
    }

    private function activeVariantBySku(?string $sku): ?ProductVariant
    {
        if ($this->blank($sku)) {
            return null;
        }

        return ProductVariant::query()->where('sku', $sku)->first();
    }

    private function imagePath(string $value, array $row, bool $variant): ?string
    {
        $value = trim(str_replace('\\', '/', $value), '/');
        $filename = basename($value);
        $candidates = Str::startsWith($value, 'uploads/products/')
            ? [$value]
            : ['uploads/products/'.$filename];

        if ($variant) {
            $candidates[] = 'uploads/products/'.Str::slug($row['product_name'] ?? '').'/variants/'.Str::slug($row['variant_name'] ?? '').'/'.$filename;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (Storage::disk('uploads')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function storeRows(array $rows): string
    {
        $path = 'product-imports/'.Str::uuid().'-rows.csv';
        Storage::disk('local')->put($path, $this->csv($rows, $this->headers()));

        return $path;
    }

    private function rowsFromStoredCsv(string $path): array
    {
        $handle = fopen(Storage::disk('local')->path($path), 'rb');

        if ($handle === false) {
            return [];
        }

        fgetcsv($handle);
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            $rows[] = $this->normalizeRow($line);
        }

        fclose($handle);

        return $rows;
    }

    private function storeErrorReport(array $errors): string
    {
        $path = 'product-imports/'.Str::uuid().'-errors.csv';
        Storage::disk('local')->put($path, $this->csv(array_map(fn ($error) => [
            'Row Number' => $error['row_number'] ?? '',
            'SKU' => $error['sku'] ?? '',
            'Column' => $error['column'] ?? '',
            'Invalid Value' => $error['invalid_value'] ?? '',
            'Reason' => $error['reason'] ?? '',
        ], $errors), ['Row Number', 'SKU', 'Column', 'Invalid Value', 'Reason']));

        return $path;
    }

    private function csv(array $rows, array $headers): string
    {
        $handle = fopen('php://temp', 'rb+');

        if ($handle === false) {
            throw new RuntimeException('Unable to open CSV stream.');
        }

        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($header) => $row[$header] ?? '', $headers));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function normalizeRow(array $line): array
    {
        $line = array_pad($line, count($this->headers()), '');

        return array_combine($this->headers(), array_slice(array_map(fn ($value) => trim((string) $value), $line), 0, count($this->headers())));
    }

    private function error(int|string $rowNumber, array $row, string $column, mixed $value, string $reason): array
    {
        return [
            'row_number' => $rowNumber,
            'sku' => $row['sku'] ?? '',
            'column' => $column,
            'invalid_value' => (string) $value,
            'reason' => $reason,
        ];
    }

    private function recordHistory(?int $userId, ?string $filename, string $duplicateAction, bool $successful, array $summary, ?string $errorReportPath): void
    {
        ProductImportHistory::query()->create([
            'user_id' => $userId,
            'filename' => $filename ?: 'products.csv',
            'rows_processed' => $summary['rows_processed'] ?? 0,
            'products_created' => $summary['products_created'] ?? 0,
            'products_updated' => $summary['products_updated'] ?? 0,
            'variants_created' => $summary['variants_created'] ?? 0,
            'variants_updated' => $summary['variants_updated'] ?? 0,
            'rows_skipped' => $summary['rows_skipped'] ?? 0,
            'rows_failed' => $summary['rows_failed'] ?? 0,
            'error_count' => $summary['errors'] ?? 0,
            'duration_seconds' => $summary['time_taken'] ?? 0,
            'successful' => $successful,
            'duplicate_action' => $duplicateAction,
            'error_report_path' => $errorReportPath,
            'summary' => $summary,
        ]);
    }

    private function summaryForFailure(array $rows, array $errors, float $startedAt): array
    {
        return [
            'rows_processed' => count($rows),
            'products_created' => 0,
            'products_updated' => 0,
            'variants_created' => 0,
            'variants_updated' => 0,
            'rows_skipped' => 0,
            'rows_failed' => collect($errors)->pluck('row_number')->unique()->count(),
            'errors' => count($errors),
            'time_taken' => $this->duration($startedAt),
        ];
    }

    private function emptyImportSummary(): array
    {
        return [
            'rows_processed' => 0,
            'products_created' => 0,
            'products_updated' => 0,
            'variants_created' => 0,
            'variants_updated' => 0,
            'images_attached' => 0,
            'rows_skipped' => 0,
            'rows_failed' => 0,
            'time_taken' => 0,
        ];
    }

    private function counts(): array
    {
        return [
            'products' => Product::query()->count(),
            'variants' => ProductVariant::query()->count(),
            'images' => ProductImage::query()->count(),
        ];
    }

    private function validateDuplicateAction(string $duplicateAction): void
    {
        if (! in_array($duplicateAction, self::DUPLICATE_ACTIONS, true)) {
            throw new InvalidArgumentException('Invalid duplicate handling option.');
        }
    }

    private function blank(mixed $value): bool
    {
        return trim((string) $value) === '';
    }

    private function duration(float $startedAt): float
    {
        return round(microtime(true) - $startedAt, 3);
    }
}
