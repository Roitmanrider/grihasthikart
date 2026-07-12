<?php

namespace App\Domains\Purchase\Services;

use App\Domains\Inventory\Services\InventoryService;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\PurchaseEntry;
use App\Models\StockLocation;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PurchaseEntryService
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {}

    public function paginate(int $perPage = 20)
    {
        return PurchaseEntry::query()
            ->with('supplier')
            ->withCount('items')
            ->latest('purchase_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): PurchaseEntry
    {
        $items = $this->normalizedItems($data['items'] ?? []);
        $totals = $this->totals($items);

        return DB::transaction(function () use ($data, $items, $totals) {
            $purchase = PurchaseEntry::query()->create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_number' => $this->generatePurchaseNumber(),
                'bill_number' => $data['bill_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'subtotal' => $totals['subtotal'],
                'gst_total' => $totals['gst_total'],
                'cgst_total' => $totals['cgst_total'],
                'sgst_total' => $totals['sgst_total'],
                'discount_total' => $totals['discount_total'],
                'grand_total' => $totals['grand_total'],
                'freight_allocation' => $data['freight_allocation'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'status' => PurchaseEntry::STATUS_POSTED,
            ]);

            foreach ($items as $item) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->findOrFail($item['product_variant_id']);

                $purchase->items()->create([
                    'product_variant_id' => $variant->id,
                    'sku' => $variant->sku,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'discount_amount' => $item['discount_amount'],
                    'gst_rate' => $item['gst_rate'],
                    'cgst_rate' => $item['cgst_rate'],
                    'sgst_rate' => $item['sgst_rate'],
                    'gst_amount' => $item['gst_amount'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_amount' => $item['sgst_amount'],
                    'line_total' => $item['line_total'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                ]);

                $inventory = $this->inventoryForVariant($variant);
                $this->inventoryService->adjustStock(
                    $inventory,
                    'purchase',
                    $item['quantity'],
                    'Purchase '.$purchase->purchase_number,
                    PurchaseEntry::class,
                    $purchase->id
                );
            }

            return $purchase->fresh(['supplier', 'items.productVariant.product']);
        });
    }

    public function options(): array
    {
        $variants = ProductVariant::query()
            ->active()
            ->with(['product', 'inventories'])
            ->whereHas('product', fn ($query) => $query->active())
            ->orderBy('sku')
            ->get();

        $suppliers = Schema::hasTable('suppliers')
            ? Supplier::query()->active()->orderBy('name')->get()
            : collect();

        return [
            'variants' => $variants,
            'suppliers' => $suppliers,
        ];
    }

    public function templateRows()
    {
        return ProductVariant::query()
            ->active()
            ->with(['product', 'inventories'])
            ->whereHas('product', fn ($query) => $query->active())
            ->orderBy('sku')
            ->get()
            ->map(function (ProductVariant $variant): array {
                return [
                    'product_name' => $variant->product?->name,
                    'variant_name' => $variant->variant_name,
                    'sku' => $variant->sku,
                    'current_stock' => number_format((float) $variant->inventories->sum(fn ($inventory) => $inventory->available_quantity), 3, '.', ''),
                    'quantity' => '',
                    'purchase_price' => '',
                    'discount' => '',
                    'gst_rate' => '',
                    'cgst_rate' => '',
                    'sgst_rate' => '',
                    'batch_number' => '',
                    'expiry_date' => '',
                ];
            });
    }

    public function previewCsv(UploadedFile $file): array
    {
        $rows = $this->csvRows($file->getRealPath());
        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('sku', collect($rows)->pluck('sku')->filter()->all())
            ->get()
            ->keyBy('sku');

        $items = [];
        $errors = [];
        $seenSkus = [];

        foreach ($rows as $line => $row) {
            if (($row['quantity'] ?? '') === '') {
                continue;
            }

            $sku = trim((string) ($row['sku'] ?? ''));

            if (isset($seenSkus[$sku])) {
                $errors[] = 'Line '.($line + 2).': Duplicate SKU '.$sku.' is not allowed in one purchase import.';

                continue;
            }

            $seenSkus[$sku] = true;
            $variant = $variants->get($sku);

            if (! $variant) {
                $errors[] = 'Line '.($line + 2).': SKU '.$sku.' was not found.';

                continue;
            }

            $item = [
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product?->name,
                'variant_name' => $variant->variant_name,
                'sku' => $variant->sku,
                'quantity' => $row['quantity'],
                'purchase_price' => $row['purchase_price'] ?? 0,
                'discount_amount' => $row['discount'] ?? 0,
                'gst_rate' => $row['gst_rate'] ?? null,
                'cgst_rate' => $row['cgst_rate'] ?? null,
                'sgst_rate' => $row['sgst_rate'] ?? null,
                'batch_number' => $row['batch_number'] ?? null,
                'expiry_date' => $row['expiry_date'] ?? null,
            ];

            try {
                $items[] = $this->normalizedItem($item);
            } catch (InvalidArgumentException $exception) {
                $errors[] = 'Line '.($line + 2).': '.$exception->getMessage();
            }
        }

        return [
            'items' => $items,
            'errors' => $errors,
            'totals' => $this->totals($items),
        ];
    }

    private function normalizedItems(array $items): array
    {
        return collect($items)
            ->map(fn (array $item): array => $this->normalizedItem($item))
            ->values()
            ->all();
    }

    private function normalizedItem(array $item): array
    {
        $quantity = (float) $item['quantity'];
        $purchasePrice = (float) $item['purchase_price'];
        $discount = round((float) ($item['discount_amount'] ?? $item['discount'] ?? 0), 2);
        $base = round($quantity * $purchasePrice, 2);

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be greater than zero.');
        }

        if ($discount < 0 || $discount > $base) {
            throw new InvalidArgumentException('Discount must be between zero and the line base amount.');
        }

        [$gstRate, $cgstRate, $sgstRate] = $this->splitRates($item);
        $expiryDate = $this->validatedDate($item['expiry_date'] ?? null);
        $taxable = round($base - $discount, 2);
        $cgstAmount = round($taxable * $cgstRate / 100, 2);
        $sgstAmount = round($taxable * $sgstRate / 100, 2);
        $gstAmount = round($cgstAmount + $sgstAmount, 2);

        return [
            'product_variant_id' => (int) $item['product_variant_id'],
            'product_name' => $item['product_name'] ?? null,
            'variant_name' => $item['variant_name'] ?? null,
            'sku' => $item['sku'] ?? null,
            'quantity' => $quantity,
            'purchase_price' => $purchasePrice,
            'discount_amount' => $discount,
            'gst_rate' => $gstRate,
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'cgst_amount' => $cgstAmount,
            'sgst_amount' => $sgstAmount,
            'gst_amount' => $gstAmount,
            'line_total' => round($taxable + $gstAmount, 2),
            'batch_number' => $item['batch_number'] ?? null,
            'expiry_date' => $expiryDate,
        ];
    }

    private function validatedDate(?string $date): ?string
    {
        if ($date === null || trim($date) === '') {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);

            if ($parsed->format('Y-m-d') !== $date) {
                throw new InvalidArgumentException('Expiry date must use YYYY-MM-DD format.');
            }

            return $parsed->toDateString();
        } catch (\Throwable) {
            throw new InvalidArgumentException('Expiry date must use YYYY-MM-DD format.');
        }
    }

    private function splitRates(array $item): array
    {
        $gstProvided = array_key_exists('gst_rate', $item) && $item['gst_rate'] !== null && $item['gst_rate'] !== '';
        $cgstProvided = array_key_exists('cgst_rate', $item) && $item['cgst_rate'] !== null && $item['cgst_rate'] !== '';
        $sgstProvided = array_key_exists('sgst_rate', $item) && $item['sgst_rate'] !== null && $item['sgst_rate'] !== '';

        $gstRate = $gstProvided ? (float) $item['gst_rate'] : 0.0;
        $cgstRate = $cgstProvided ? (float) $item['cgst_rate'] : null;
        $sgstRate = $sgstProvided ? (float) $item['sgst_rate'] : null;

        if ($gstProvided && (float) ($cgstRate ?? 0) === 0.0 && (float) ($sgstRate ?? 0) === 0.0 && $gstRate > 0) {
            $cgstRate = round($gstRate / 2, 2);
            $sgstRate = round($gstRate / 2, 2);
        } elseif ($cgstRate === null && $sgstRate === null) {
            $cgstRate = round($gstRate / 2, 2);
            $sgstRate = round($gstRate / 2, 2);
        } elseif ($cgstRate !== null && $sgstRate === null) {
            $sgstRate = $cgstRate;
            $gstRate = round($cgstRate + $sgstRate, 2);
        } elseif ($cgstRate === null && $sgstRate !== null) {
            $cgstRate = $sgstRate;
            $gstRate = round($cgstRate + $sgstRate, 2);
        } else {
            $gstRate = round($cgstRate + $sgstRate, 2);
        }

        foreach (['GST' => $gstRate, 'CGST' => $cgstRate, 'SGST' => $sgstRate] as $label => $rate) {
            if ($rate < 0) {
                throw new InvalidArgumentException($label.' rate cannot be negative.');
            }
        }

        return [round($gstRate, 2), round($cgstRate, 2), round($sgstRate, 2)];
    }

    private function totals(array $items): array
    {
        $subtotal = round(collect($items)->sum(fn (array $item) => $item['quantity'] * $item['purchase_price']), 2);
        $discountTotal = round(collect($items)->sum('discount_amount'), 2);
        $cgstTotal = round(collect($items)->sum('cgst_amount'), 2);
        $sgstTotal = round(collect($items)->sum('sgst_amount'), 2);
        $gstTotal = round(collect($items)->sum('gst_amount'), 2);

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'cgst_total' => $cgstTotal,
            'sgst_total' => $sgstTotal,
            'gst_total' => $gstTotal,
            'grand_total' => round($subtotal - $discountTotal + $gstTotal, 2),
        ];
    }

    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            throw new InvalidArgumentException('Unable to read uploaded CSV file.');
        }

        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);

            return [];
        }

        $header = collect($header)
            ->map(fn ($column) => trim((string) $column, " \t\n\r\0\x0B\xEF\xBB\xBF"))
            ->all();
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($header, array_slice(array_pad($line, count($header), ''), 0, count($header)));
        }

        fclose($handle);

        return $rows;
    }

    private function inventoryForVariant(ProductVariant $variant): Inventory
    {
        $location = StockLocation::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('id')
            ->first();

        if (! $location) {
            throw new InvalidArgumentException('Create an active stock location before posting purchases.');
        }

        /** @var Inventory $inventory */
        $inventory = Inventory::query()->firstOrCreate(
            [
                'product_variant_id' => $variant->id,
                'stock_location_id' => $location->id,
            ],
            [
                'quantity_on_hand' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'status' => true,
            ]
        );

        return $inventory;
    }

    private function generatePurchaseNumber(): string
    {
        do {
            $number = 'PUR'.now()->format('ymd').Str::upper(Str::random(5));
        } while (PurchaseEntry::query()->where('purchase_number', $number)->exists());

        return $number;
    }
}
