<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FsComProduct;
use App\Services\FsComImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FsComCatalogController extends Controller
{
    protected $importService;

    public function __construct(FsComImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Get catalog statistics
     */
    public function stats()
    {
        $stats = [
            'total_products' => FsComProduct::count(),
            'total_gbics' => FsComProduct::where('category', 'gbic')->count(),
            'total_patch_cords' => FsComProduct::where('category', 'patch_cord')->count(),
            'last_sync' => FsComProduct::max('last_synced_at'),
        ];

        return response()->json($stats);
    }

    /**
     * Get all products
     */
    public function index(Request $request)
    {
        $query = FsComProduct::query();

        // Filter by category
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Generate CSV template
     */
    public function generateTemplate()
    {
        try {
            $filename = 'fscom_template_' . now()->format('Y-m-d_His') . '.csv';
            $path = storage_path('app/public/' . $filename);

            // Generate template
            $this->importService->generateTemplate($path);

            return response()->json([
                'success' => true,
                'message' => 'Template generated successfully',
                'filename' => $filename,
                'download_url' => asset('storage/' . $filename)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate CSV file before import
     */
    public function validateCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240' // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $path = $file->store('temp');
            $fullPath = storage_path('app/' . $path);

            // Validate in dry-run mode
            $result = $this->importService->importFromCsv($fullPath, [
                'dry_run' => true,
                'skip_errors' => true
            ]);

            // Clean up temp file
            Storage::delete($path);

            return response()->json([
                'success' => true,
                'stats' => $result['stats'],
                'errors' => $result['errors'],
                'has_errors' => count($result['errors']) > 0
            ]);
        } catch (\Exception $e) {
            Log::error('CSV validation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import products from CSV
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'stop_on_error' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $path = $file->store('imports');
            $fullPath = storage_path('app/' . $path);

            // Import
            $result = $this->importService->importFromCsv($fullPath, [
                'dry_run' => false,
                'skip_errors' => !$request->boolean('stop_on_error', false)
            ]);

            // Keep import file for history
            // Don't delete it immediately

            return response()->json([
                'success' => true,
                'message' => 'Import completed',
                'stats' => $result['stats'],
                'errors' => $result['errors'],
                'has_errors' => count($result['errors']) > 0
            ]);
        } catch (\Exception $e) {
            Log::error('CSV import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export products to CSV
     */
    public function export(Request $request)
    {
        try {
            $category = $request->get('category', null);
            $filename = 'fscom_export_' . now()->format('Y-m-d_His') . '.csv';
            $path = storage_path('app/public/' . $filename);

            // Export
            $count = $this->importService->exportToCsv($path, $category);

            return response()->json([
                'success' => true,
                'message' => "Exported {$count} products",
                'filename' => $filename,
                'download_url' => asset('storage/' . $filename),
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('CSV export failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product
     */
    public function destroy($id)
    {
        try {
            $product = FsComProduct::findOrFail($id);
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete product: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product'
            ], 500);
        }
    }

    /**
     * Bulk delete products
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:fs_com_products,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $count = FsComProduct::whereIn('id', $request->ids)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} products deleted successfully",
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk delete failed'
            ], 500);
        }
    }
}
