<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\BrandService;
use App\Domains\Catalog\Services\CategoryService;
use App\Domains\Catalog\Services\ProductCatalogImportExportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportProductImportRequest;
use App\Http\Requests\PreviewProductImportRequest;
use App\Models\ProductImportHistory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProductImportController extends Controller
{
    private const SESSION_ROWS_KEY = 'product_import.rows';

    private const SESSION_PREVIEW_KEY = 'product_import.preview';

    private const SESSION_ROW_PATH_KEY = 'product_import.row_path';

    private const SESSION_ERROR_REPORT_KEY = 'product_import.error_report_path';

    private const SESSION_DUPLICATE_ACTION_KEY = 'product_import.duplicate_action';

    private const SESSION_FILENAME_KEY = 'product_import.filename';

    public function __construct(
        private readonly ProductCatalogImportExportService $productImportService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService
    ) {}

    public function index(Request $request)
    {
        return view('admin.product-imports.index', [
            'headers' => $this->productImportService->headers(),
            'requiredHeaders' => $this->productImportService->requiredHeaders(),
            'duplicateActions' => ProductCatalogImportExportService::DUPLICATE_ACTIONS,
            'selectedDuplicateAction' => $request->session()->get(self::SESSION_DUPLICATE_ACTION_KEY, ProductCatalogImportExportService::DUPLICATE_UPDATE),
            'preview' => $request->session()->get(self::SESSION_PREVIEW_KEY),
            'categories' => $this->categoryService->activeCategories(),
            'brands' => $this->brandService->activeBrands(),
            'suppliers' => Supplier::query()->active()->orderBy('name')->get(),
            'histories' => $this->productImportService->histories(),
        ]);
    }

    public function template()
    {
        return response()->streamDownload(function () {
            echo $this->productImportService->template();
        }, 'Blank Product Template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function preview(PreviewProductImportRequest $request)
    {
        try {
            $preview = $this->productImportService->preview(
                $request->file('csv_file'),
                $request->input('duplicate_action', ProductCatalogImportExportService::DUPLICATE_UPDATE)
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['csv_file' => $exception->getMessage()]);
        }

        $request->session()->forget(self::SESSION_ROWS_KEY);
        $request->session()->put(self::SESSION_ROW_PATH_KEY, $preview['row_path']);
        $request->session()->put(self::SESSION_ERROR_REPORT_KEY, $preview['error_report_path']);
        $request->session()->put(self::SESSION_DUPLICATE_ACTION_KEY, $request->input('duplicate_action', ProductCatalogImportExportService::DUPLICATE_UPDATE));
        $request->session()->put(self::SESSION_FILENAME_KEY, $request->file('csv_file')->getClientOriginalName());
        $request->session()->put(self::SESSION_PREVIEW_KEY, $preview);

        return redirect()
            ->route('admin.product-imports.index')
            ->with($preview['has_errors'] ? 'warning' : 'success', $preview['has_errors'] ? 'CSV preview has errors. Fix them before importing.' : 'CSV preview is ready to import.');
    }

    public function import(ImportProductImportRequest $request)
    {
        $rowPath = (string) $request->session()->get(self::SESSION_ROW_PATH_KEY, '');
        $duplicateAction = (string) $request->session()->get(self::SESSION_DUPLICATE_ACTION_KEY, ProductCatalogImportExportService::DUPLICATE_UPDATE);

        try {
            $summary = $this->productImportService->import(
                $rowPath,
                $duplicateAction,
                $request->user()?->id,
                $request->session()->get(self::SESSION_FILENAME_KEY, 'products.csv')
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.product-imports.index')
                ->withErrors(['import' => $exception->getMessage()]);
        }

        $request->session()->forget([self::SESSION_ROWS_KEY, self::SESSION_ROW_PATH_KEY, self::SESSION_PREVIEW_KEY, self::SESSION_ERROR_REPORT_KEY, self::SESSION_DUPLICATE_ACTION_KEY, self::SESSION_FILENAME_KEY]);

        return redirect()
            ->route('admin.product-imports.index')
            ->with('success', 'Product import completed successfully.')
            ->with('import_summary', $summary);
    }

    public function errorReport(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            echo $this->productImportService->errorReport($request->session()->get(self::SESSION_ERROR_REPORT_KEY));
        }, 'CSV_Error_Report.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function historyErrorReport(ProductImportHistory $history)
    {
        return response()->streamDownload(function () use ($history) {
            echo $this->productImportService->errorReport($history->error_report_path);
        }, 'CSV_Error_Report.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function export(Request $request)
    {
        return response()->streamDownload(function () use ($request) {
            echo $this->productImportService->export($request->only([
                'category_id',
                'brand_id',
                'status',
                'supplier_id',
                'created_from',
                'created_to',
                'updated_from',
                'updated_to',
            ]));
        }, 'Product_Catalog.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
