<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\ProductImageRepositoryInterface;
use App\Domains\Catalog\Contracts\ProductVariantRepositoryInterface;
use App\Domains\Inventory\Services\InventoryService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductImportService
{
    public const HEADERS = [
        'product_name',
        'brand_name',
        'category',
        'subcategory',
        'sub_subcategory',
        'variant_name',
        'sku',
        'mrp',
        'selling_price',
        'purchase_price',
        'gst_rate',
        'hsn_code',
        'barcode',
        'weight',
        'unit',
        'opening_stock',
        'low_stock_threshold',
        'reorder_level',
        'target_stock_level',
        'is_featured',
        'is_trending',
        'is_popular',
        'is_new_arrival',
        'status',
        'product_image',
        'variant_image',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    public const REQUIRED_HEADERS = [
        'product_name',
        'category',
        'variant_name',
        'sku',
        'mrp',
        'selling_price',
    ];

    public function __construct(
        private readonly ProductService $productService,
        private readonly BrandService $brandService,
        private readonly ProductVariantRepositoryInterface $variantRepository,
        private readonly ProductImageRepositoryInterface $imageRepository,
        private readonly InventoryService $inventoryService
    ) {}

    public function csvTemplate(): string
    {
        return implode(',', self::HEADERS).PHP_EOL;
    }

    public function preview(UploadedFile $file): array
    {
        $rows = $this->parseCsv($file);

        $previewRows = [];
        $hasErrors = false;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $result = $this->validateRow($row);
            $hasErrors = $hasErrors || $result['errors'] !== [];
            $previewRows[] = [
                'row_number' => $rowNumber,
                'data' => $row,
                'status' => $result['status'],
                'errors' => $result['errors'],
                'warnings' => $result['warnings'],
            ];
        }

        return [
            'rows' => $previewRows,
            'valid_rows' => collect($previewRows)->where('errors', [])->count(),
            'error_rows' => collect($previewRows)->reject(fn (array $row) => $row['errors'] === [])->count(),
            'has_errors' => $hasErrors,
        ];
    }

    public function import(array $rows): array
    {
        if ($rows === []) {
            throw new InvalidArgumentException('Upload and preview a valid CSV before importing.');
        }

        $preview = $this->previewRows($rows);

        if ($preview['has_errors']) {
            throw new InvalidArgumentException('Fix CSV validation errors before importing.');
        }

        return DB::transaction(function () use ($rows) {
            $summary = [
                'products_created' => 0,
                'products_updated' => 0,
                'variants_created' => 0,
                'variants_updated' => 0,
                'inventories_created' => 0,
                'inventories_updated' => 0,
                'images_attached' => 0,
            ];

            foreach ($rows as $row) {
                $result = $this->importRow($row);

                foreach ($summary as $key => $value) {
                    $summary[$key] += $result[$key] ?? 0;
                }
            }

            return $summary;
        });
    }

    public function previewRows(array $rows): array
    {
        $previewRows = [];
        $hasErrors = false;

        foreach ($rows as $index => $row) {
            $result = $this->validateRow($row);
            $hasErrors = $hasErrors || $result['errors'] !== [];
            $previewRows[] = [
                'row_number' => $index + 2,
                'data' => $row,
                'status' => $result['status'],
                'errors' => $result['errors'],
                'warnings' => $result['warnings'],
            ];
        }

        return [
            'rows' => $previewRows,
            'valid_rows' => collect($previewRows)->where('errors', [])->count(),
            'error_rows' => collect($previewRows)->reject(fn (array $row) => $row['errors'] === [])->count(),
            'has_errors' => $hasErrors,
        ];
    }

    private function parseCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new InvalidArgumentException('Unable to read uploaded CSV file.');
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            throw new InvalidArgumentException('CSV file is empty.');
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);

        if ($headers !== self::HEADERS) {
            fclose($handle);

            throw new InvalidArgumentException('CSV headers must exactly match the Product Import template.');
        }

        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($this->isBlankLine($line)) {
                continue;
            }

            $line = array_pad($line, count(self::HEADERS), '');
            $rows[] = array_combine(self::HEADERS, array_slice(array_map(fn ($value) => trim((string) $value), $line), 0, count(self::HEADERS)));
        }

        fclose($handle);

        if ($rows === []) {
            throw new InvalidArgumentException('CSV file does not contain import rows.');
        }

        return $rows;
    }

    private function validateRow(array $row): array
    {
        $errors = [];
        $warnings = [];

        foreach (self::REQUIRED_HEADERS as $field) {
            if ($this->blank($row[$field] ?? null)) {
                $errors[] = str_replace('_', ' ', $field).' is required.';
            }
        }

        $categoryResult = $this->resolveCategoryPath($row);
        $errors = array_merge($errors, $categoryResult['errors']);

        $product = $this->findProduct($row);
        $variant = $this->findVariantBySku($row['sku'] ?? '');

        if ($variant !== null && ($product === null || $variant->product_id !== $product->id)) {
            $errors[] = 'SKU already belongs to a different product.';
        }

        if (! $this->blank($row['barcode'] ?? null)) {
            $barcodeVariant = $this->findVariantByBarcode($row['barcode']);

            if ($barcodeVariant !== null && ($variant === null || $barcodeVariant->id !== $variant->id)) {
                $errors[] = 'Barcode already belongs to another product variant.';
            }
        }

        $this->validatePrices($row, $errors);
        $this->validateInventoryColumns($row, $errors);
        $this->validateImages($row, $errors, $warnings);

        $status = $this->booleanValue($row['status'] ?? null, true);

        if ($product !== null && ! $product->status && $status) {
            $errors[] = 'Active variants cannot be imported under an inactive product.';
        }

        $importStatus = match (true) {
            $variant !== null => 'update variant',
            $product !== null => 'create variant',
            default => 'create product and variant',
        };

        return [
            'status' => $importStatus,
            'errors' => array_values(array_unique($errors)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function importRow(array $row): array
    {
        $summary = [];
        $categoryPayload = $this->categoryPayload($row);
        $brand = $this->resolveBrand($row['brand_name'] ?? '');
        $product = $this->findProduct($row);
        $productData = $this->productData($row, $brand?->id, $categoryPayload);

        if ($product === null) {
            $product = $this->productService->create($productData);
            $summary['products_created'] = 1;
        } else {
            $product = $this->productService->update($product, $productData);
            $summary['products_updated'] = 1;
        }

        $variant = $this->findVariantBySku($row['sku']);

        if ($variant !== null && $variant->product_id !== $product->id) {
            throw new InvalidArgumentException('SKU '.$row['sku'].' already belongs to a different product.');
        }

        $variantData = $this->variantData($row, $product, $variant);

        if ($variant === null) {
            /** @var ProductVariant $variant */
            $variant = $this->variantRepository->create($variantData);
            $summary['variants_created'] = 1;
        } else {
            /** @var ProductVariant $variant */
            $variant = $this->variantRepository->update($variant, $variantData);
            $summary['variants_updated'] = 1;
        }

        $this->syncDefaultVariant($product->fresh(), $variant);
        $summary = array_merge($summary, $this->syncInventory($variant, $row));
        $summary['images_attached'] = $this->attachImages($product->fresh(), $variant->fresh(), $row);

        return $summary;
    }

    private function productData(array $row, ?int $brandId, array $categoryPayload): array
    {
        return [
            'brand_id' => $brandId,
            'category_ids' => $categoryPayload['category_ids'],
            'primary_category_id' => $categoryPayload['primary_category_id'],
            'name' => $row['product_name'],
            'short_description' => $this->nullable($row['short_description'] ?? null),
            'description' => $this->nullable($row['description'] ?? null),
            'hsn_code' => $this->nullable($row['hsn_code'] ?? null),
            'gst_rate' => $this->nullable($row['gst_rate'] ?? null),
            'is_featured' => $this->booleanValue($row['is_featured'] ?? null, false),
            'is_trending' => $this->booleanValue($row['is_trending'] ?? null, false),
            'is_popular' => $this->booleanValue($row['is_popular'] ?? null, false),
            'is_new_arrival' => $this->booleanValue($row['is_new_arrival'] ?? null, false),
            'status' => $this->booleanValue($row['status'] ?? null, true),
            'minimum_order_quantity' => 1,
            'returnable' => true,
            'cod_available' => true,
            'meta_title' => $this->nullable($row['meta_title'] ?? null),
            'meta_description' => $this->nullable($row['meta_description'] ?? null),
            'meta_keywords' => $this->nullable($row['meta_keywords'] ?? null),
        ];
    }

    private function variantData(array $row, Product $product, ?ProductVariant $variant): array
    {
        $status = $this->booleanValue($row['status'] ?? null, true);

        if ($status && ! $product->status) {
            throw new InvalidArgumentException('Active variants cannot be imported under an inactive product.');
        }

        $signature = $variant?->attribute_signature ?: 'import:'.Str::slug($row['variant_name']);

        if ($this->variantCombinationExists($product, $signature, $variant?->id)) {
            throw new InvalidArgumentException('This product already has an imported variant with the same variant name.');
        }

        return [
            'product_id' => $product->id,
            'sku' => $row['sku'],
            'barcode' => $this->nullable($row['barcode'] ?? null),
            'variant_name' => $row['variant_name'],
            'attribute_signature' => $signature,
            'weight' => $this->nullable($row['weight'] ?? null),
            'unit' => $this->nullable($row['unit'] ?? null),
            'mrp' => $row['mrp'],
            'selling_price' => $row['selling_price'],
            'purchase_price' => $this->nullable($row['purchase_price'] ?? null),
            'is_default' => $variant?->is_default ?? $this->shouldBeDefaultVariant($product),
            'status' => $status,
            'display_order' => $variant?->display_order ?? 0,
        ];
    }

    private function syncDefaultVariant(Product $product, ProductVariant $variant): void
    {
        if (! $variant->is_default) {
            return;
        }

        $this->variantRepository->clearDefaultForProduct($product->id, $variant->id);
        $product->forceFill(['default_variant_id' => $variant->id])->save();
    }

    private function syncInventory(ProductVariant $variant, array $row): array
    {
        if (! $this->hasInventoryData($row)) {
            return [];
        }

        $location = StockLocation::query()
            ->active()
            ->where('is_default', true)
            ->first();

        if (! $location) {
            throw new InvalidArgumentException('Default stock location is required before importing opening stock.');
        }

        $inventory = Inventory::query()
            ->where('product_variant_id', $variant->id)
            ->where('stock_location_id', $location->id)
            ->first();

        $quantity = (float) ($this->blank($row['opening_stock'] ?? null) ? 0 : $row['opening_stock']);
        $thresholdData = [
            'low_stock_threshold' => $this->nullable($row['low_stock_threshold'] ?? null),
            'reorder_level' => $this->nullable($row['reorder_level'] ?? null),
            'target_stock_level' => $this->nullable($row['target_stock_level'] ?? null),
            'status' => true,
        ];

        if ($inventory === null) {
            $this->inventoryService->createInventory($variant, $location, array_merge($thresholdData, [
                'quantity_on_hand' => $quantity,
            ]));

            return ['inventories_created' => 1];
        }

        $this->inventoryService->update($inventory, $thresholdData);

        $delta = $quantity - (float) $inventory->fresh()->quantity_on_hand;

        if ($delta > 0) {
            $this->inventoryService->adjustStock($inventory->fresh(), 'adjustment_in', $delta, 'Product import stock update');
        } elseif ($delta < 0) {
            $this->inventoryService->adjustStock($inventory->fresh(), 'adjustment_out', abs($delta), 'Product import stock update');
        }

        return ['inventories_updated' => 1];
    }

    private function attachImages(Product $product, ProductVariant $variant, array $row): int
    {
        $count = 0;
        $productImage = $this->resolveImagePath($row['product_image'] ?? '', $row, false);

        if ($productImage !== null && ! $this->imageExists($product->id, null, $productImage)) {
            $image = $this->imageRepository->create([
                'product_id' => $product->id,
                'product_variant_id' => null,
                'path' => $productImage,
                'alt_text' => $product->name,
                'title' => $product->name,
                'display_order' => 0,
                'is_primary' => ! $product->images()->exists(),
                'status' => true,
            ]);

            if ($image->is_primary) {
                $this->imageRepository->clearPrimaryForProduct($product->id, $image->id);
            }

            $count++;
        }

        $variantImage = $this->resolveImagePath($row['variant_image'] ?? '', $row, true);

        if ($variantImage !== null && ! $this->imageExists($product->id, $variant->id, $variantImage)) {
            $image = $this->imageRepository->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'path' => $variantImage,
                'alt_text' => $product->name.' '.$variant->variant_name,
                'title' => $variant->variant_name,
                'display_order' => 0,
                'is_primary' => ! $variant->images()->exists(),
                'status' => true,
            ]);

            if ($image->is_primary) {
                $this->imageRepository->clearPrimaryForVariant($variant->id, $image->id);
            }

            $count++;
        }

        return $count;
    }

    private function imageExists(int $productId, ?int $variantId, string $path): bool
    {
        return ProductImage::query()
            ->where('product_id', $productId)
            ->where('product_variant_id', $variantId)
            ->where('path', $path)
            ->exists();
    }

    private function resolveBrand(?string $brandName): ?Brand
    {
        if ($this->blank($brandName)) {
            return null;
        }

        $brand = Brand::query()
            ->where('name', $brandName)
            ->orWhere('slug', Str::slug($brandName))
            ->first();

        if ($brand !== null) {
            return $brand;
        }

        return $this->brandService->create([
            'name' => $brandName,
            'status' => true,
        ]);
    }

    private function findProduct(array $row): ?Product
    {
        $name = $row['product_name'] ?? '';

        if ($this->blank($name)) {
            return null;
        }

        return Product::query()
            ->where('name', $name)
            ->orWhere('slug', Str::slug($name))
            ->first();
    }

    private function findVariantBySku(?string $sku): ?ProductVariant
    {
        if ($this->blank($sku)) {
            return null;
        }

        return ProductVariant::query()
            ->where('sku', $sku)
            ->first();
    }

    private function findVariantByBarcode(?string $barcode): ?ProductVariant
    {
        if ($this->blank($barcode)) {
            return null;
        }

        return ProductVariant::query()
            ->where('barcode', $barcode)
            ->first();
    }

    private function categoryPayload(array $row): array
    {
        $resolved = $this->resolveCategoryPath($row);

        if ($resolved['errors'] !== []) {
            throw new InvalidArgumentException(implode(' ', $resolved['errors']));
        }

        $categoryIds = collect($resolved['categories'])
            ->pluck('id')
            ->values()
            ->all();

        return [
            'category_ids' => $categoryIds,
            'primary_category_id' => end($categoryIds),
        ];
    }

    private function resolveCategoryPath(array $row): array
    {
        $errors = [];
        $categories = [];
        $category = $this->findCategoryByNameOrSlug($row['category'] ?? '');

        if (! $category) {
            return [
                'categories' => [],
                'errors' => ['Category was not found.'],
            ];
        }

        $categories[] = $category;

        if (! $this->blank($row['subcategory'] ?? null)) {
            $subcategory = $this->findCategoryByNameOrSlug($row['subcategory'], $category->id);

            if (! $subcategory) {
                $errors[] = 'Subcategory was not found under the selected category.';
            } else {
                $categories[] = $subcategory;
            }
        }

        if (! $this->blank($row['sub_subcategory'] ?? null)) {
            if (count($categories) < 2) {
                $errors[] = 'Sub-subcategory requires a matching subcategory.';
            } else {
                $subSubcategory = $this->findCategoryByNameOrSlug($row['sub_subcategory'], $categories[1]->id);

                if (! $subSubcategory) {
                    $errors[] = 'Sub-subcategory was not found under the selected subcategory.';
                } else {
                    $categories[] = $subSubcategory;
                }
            }
        }

        return [
            'categories' => $categories,
            'errors' => $errors,
        ];
    }

    private function findCategoryByNameOrSlug(?string $value, ?int $parentId = null): ?Category
    {
        if ($this->blank($value)) {
            return null;
        }

        return Category::query()
            ->where('parent_id', $parentId)
            ->where(function ($query) use ($value) {
                $query->where('name', $value)
                    ->orWhere('slug', Str::slug($value));
            })
            ->first();
    }

    private function validatePrices(array $row, array &$errors): void
    {
        foreach (['mrp', 'selling_price', 'purchase_price', 'gst_rate', 'weight', 'opening_stock', 'low_stock_threshold', 'reorder_level', 'target_stock_level'] as $field) {
            if (! $this->blank($row[$field] ?? null) && ! is_numeric($row[$field])) {
                $errors[] = str_replace('_', ' ', $field).' must be numeric.';
            }
        }

        if (is_numeric($row['mrp'] ?? null) && is_numeric($row['selling_price'] ?? null) && (float) $row['selling_price'] > (float) $row['mrp']) {
            $errors[] = 'Selling price cannot be greater than MRP.';
        }

        if (! $this->blank($row['purchase_price'] ?? null) && is_numeric($row['purchase_price']) && is_numeric($row['mrp'] ?? null) && (float) $row['purchase_price'] > (float) $row['mrp']) {
            $errors[] = 'Purchase price cannot be greater than MRP.';
        }
    }

    private function validateInventoryColumns(array $row, array &$errors): void
    {
        if (! $this->hasInventoryData($row)) {
            return;
        }

        if (! StockLocation::query()->active()->where('is_default', true)->exists()) {
            $errors[] = 'Default stock location is required before importing opening stock.';
        }

        foreach (['opening_stock', 'low_stock_threshold', 'reorder_level', 'target_stock_level'] as $field) {
            if (! $this->blank($row[$field] ?? null) && (float) $row[$field] < 0) {
                $errors[] = str_replace('_', ' ', $field).' cannot be negative.';
            }
        }
    }

    private function validateImages(array $row, array &$errors, array &$warnings): void
    {
        foreach (['product_image' => false, 'variant_image' => true] as $field => $isVariant) {
            if ($this->blank($row[$field] ?? null)) {
                continue;
            }

            if ($this->resolveImagePath($row[$field], $row, $isVariant) === null) {
                $errors[] = str_replace('_', ' ', $field).' was not found under uploads/products.';
            }
        }

        if ($this->blank($row['sub_subcategory'] ?? null)) {
            $warnings[] = 'sub_subcategory is blank; product will be assigned to the deepest provided category.';
        }
    }

    private function resolveImagePath(?string $value, array $row, bool $variant): ?string
    {
        if ($this->blank($value)) {
            return null;
        }

        $value = trim(str_replace('\\', '/', $value), '/');
        $filename = basename($value);
        $productSlug = Str::slug($row['product_name'] ?? '');
        $variantSlug = Str::slug($row['variant_name'] ?? '');
        $candidates = Str::startsWith($value, 'uploads/products/')
            ? [$value]
            : ['uploads/products/'.$filename];

        if ($variant) {
            $candidates[] = 'uploads/products/'.$productSlug.'/variants/'.$variantSlug.'/'.$filename;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (Storage::disk('uploads')->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function variantCombinationExists(Product $product, string $signature, ?int $ignoreId): bool
    {
        return ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('attribute_signature', $signature)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    private function shouldBeDefaultVariant(Product $product): bool
    {
        return ! ProductVariant::query()->where('product_id', $product->id)->exists()
            || $product->default_variant_id === null;
    }

    private function hasInventoryData(array $row): bool
    {
        foreach (['opening_stock', 'low_stock_threshold', 'reorder_level', 'target_stock_level'] as $field) {
            if (! $this->blank($row[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function booleanValue(?string $value, bool $default): bool
    {
        if ($this->blank($value)) {
            return $default;
        }

        return in_array(Str::lower((string) $value), ['1', 'true', 'yes', 'y', 'active'], true);
    }

    private function normalizeHeader(string $header): string
    {
        return trim(preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header);
    }

    private function nullable(?string $value): ?string
    {
        return $this->blank($value) ? null : $value;
    }

    private function blank(mixed $value): bool
    {
        return trim((string) $value) === '';
    }

    private function isBlankLine(array $line): bool
    {
        return collect($line)->every(fn ($value) => $this->blank($value));
    }
}
