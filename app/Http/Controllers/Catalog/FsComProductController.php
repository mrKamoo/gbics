<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\FsComProduct;
use App\Services\FsComScraperService;
use Illuminate\Http\Request;

class FsComProductController extends Controller
{
    /**
     * Display a listing of FS.com products.
     */
    public function index(Request $request)
    {
        $query = FsComProduct::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $products = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($products);
    }

    /**
     * Display the specified product.
     */
    public function show(FsComProduct $fsComProduct)
    {
        return response()->json([
            'product' => $fsComProduct,
        ]);
    }

    /**
     * Get available categories.
     */
    public function categories()
    {
        $categories = FsComProduct::select('category')
            ->selectRaw('count(*) as count')
            ->groupBy('category')
            ->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Search products.
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2',
            'category' => 'nullable|in:gbic,patch_cord',
            'limit' => 'nullable|integer|max:50',
        ]);

        $query = FsComProduct::query();

        // Search in name, SKU, and description
        $query->where(function ($q) use ($validated) {
            $q->where('name', 'like', "%{$validated['q']}%")
              ->orWhere('sku', 'like', "%{$validated['q']}%")
              ->orWhere('description', 'like', "%{$validated['q']}%");
        });

        // Filter by category if specified
        if (isset($validated['category'])) {
            $query->where('category', $validated['category']);
        }

        $limit = $validated['limit'] ?? 20;
        $products = $query->limit($limit)->get();

        return response()->json([
            'products' => $products,
            'count' => $products->count(),
        ]);
    }

    /**
     * Force synchronization with FS.com.
     */
    public function sync(Request $request, FsComScraperService $scraper)
    {
        $validated = $request->validate([
            'category' => 'nullable|in:gbic,patch_cord',
        ]);

        try {
            $stats = isset($validated['category'])
                ? $scraper->scrapeCategory(
                    $validated['category'],
                    $validated['category'] === 'gbic'
                        ? '/c/optical-modules-transceivers'
                        : '/c/fiber-patch-cables'
                  )
                : $scraper->syncAll();

            return response()->json([
                'message' => 'Synchronization completed successfully',
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Synchronization failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get synchronization statistics.
     */
    public function stats(FsComScraperService $scraper)
    {
        $stats = $scraper->getStats();

        return response()->json([
            'stats' => $stats,
        ]);
    }
}
