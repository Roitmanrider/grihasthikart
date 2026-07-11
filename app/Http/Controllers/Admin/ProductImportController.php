<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Catalog\Services\ProductImportService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportProductImportRequest;
use App\Http\Requests\PreviewProductImportRequest;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ProductImportController extends Controller
{
    private const SESSION_ROWS_KEY = 'product_import.rows';

    private const SESSION_PREVIEW_KEY = 'product_import.preview';

    public function __construct(
        private readonly ProductImportService $productImportService
    ) {}

    public function index(Request $request)
    {
        return view('admin.product-imports.index', [
            'headers' => ProductImportService::HEADERS,
            'requiredHeaders' => ProductImportService::REQUIRED_HEADERS,
            'preview' => $request->session()->get(self::SESSION_PREVIEW_KEY),
        ]);
    }

    public function template()
    {
        return response()->streamDownload(function () {
            echo $this->productImportService->csvTemplate();
        }, 'grihasthikart-product-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function preview(PreviewProductImportRequest $request)
    {
        try {
            $preview = $this->productImportService->preview($request->file('csv_file'));
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['csv_file' => $exception->getMessage()]);
        }

        $request->session()->put(self::SESSION_ROWS_KEY, collect($preview['rows'])->pluck('data')->all());
        $request->session()->put(self::SESSION_PREVIEW_KEY, $preview);

        return redirect()
            ->route('admin.product-imports.index')
            ->with($preview['has_errors'] ? 'warning' : 'success', $preview['has_errors'] ? 'CSV preview has errors. Fix them before importing.' : 'CSV preview is ready to import.');
    }

    public function import(ImportProductImportRequest $request)
    {
        $rows = $request->session()->get(self::SESSION_ROWS_KEY, []);

        try {
            $summary = $this->productImportService->import($rows);
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('admin.product-imports.index')
                ->withErrors(['import' => $exception->getMessage()]);
        }

        $request->session()->forget([self::SESSION_ROWS_KEY, self::SESSION_PREVIEW_KEY]);

        return redirect()
            ->route('admin.product-imports.index')
            ->with('success', 'Product import completed successfully.')
            ->with('import_summary', $summary);
    }
}
