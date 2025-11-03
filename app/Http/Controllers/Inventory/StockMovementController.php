<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = StockMovement::query()
            ->with(['movable', 'fromSite', 'toSite', 'performedByUser']);

        // Filter by movement type
        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // Filter by movable type
        if ($request->has('movable_type')) {
            $query->where('movable_type', $request->movable_type);
        }

        // Filter by site
        if ($request->has('site_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_site_id', $request->site_id)
                  ->orWhere('to_site_id', $request->site_id);
            });
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('performed_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('performed_at', '<=', $request->to_date);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'performed_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $movements = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($movements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'movable_type' => 'required|in:App\\Models\\Gbic,App\\Models\\PatchCord,App\\Models\\NetworkSwitch',
            'movable_id' => 'required|integer',
            'movement_type' => 'required|in:in,out,transfer,return,adjustment',
            'from_site_id' => 'nullable|exists:sites,id',
            'to_site_id' => 'nullable|exists:sites,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        // Validate movement logic
        if ($validated['movement_type'] === 'transfer') {
            if (empty($validated['from_site_id']) || empty($validated['to_site_id'])) {
                return response()->json([
                    'message' => 'Transfer requires both from_site_id and to_site_id',
                ], 400);
            }
            if ($validated['from_site_id'] === $validated['to_site_id']) {
                return response()->json([
                    'message' => 'Cannot transfer to the same site',
                ], 400);
            }
        }

        if ($validated['movement_type'] === 'in' && empty($validated['to_site_id'])) {
            return response()->json([
                'message' => 'Movement type "in" requires to_site_id',
            ], 400);
        }

        if ($validated['movement_type'] === 'out' && empty($validated['from_site_id'])) {
            return response()->json([
                'message' => 'Movement type "out" requires from_site_id',
            ], 400);
        }

        // Check if movable exists
        $movableClass = $validated['movable_type'];
        $movable = $movableClass::find($validated['movable_id']);

        if (!$movable) {
            return response()->json([
                'message' => 'Asset not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $movement = StockMovement::create([
                'movable_type' => $validated['movable_type'],
                'movable_id' => $validated['movable_id'],
                'movement_type' => $validated['movement_type'],
                'from_site_id' => $validated['from_site_id'] ?? null,
                'to_site_id' => $validated['to_site_id'] ?? null,
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'] ?? null,
                'performed_by' => $request->user()->id,
                'performed_at' => now(),
            ]);

            DB::commit();

            $movement->load(['movable', 'fromSite', 'toSite', 'performedByUser']);

            return response()->json([
                'message' => 'Stock movement recorded successfully',
                'movement' => $movement,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to record stock movement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StockMovement $stockMovement)
    {
        $stockMovement->load([
            'movable',
            'fromSite',
            'toSite',
            'performedByUser'
        ]);

        return response()->json([
            'stock_movement' => $stockMovement,
        ]);
    }

    /**
     * Get movements for a specific asset.
     */
    public function byAsset(Request $request)
    {
        $validated = $request->validate([
            'movable_type' => 'required|in:App\\Models\\Gbic,App\\Models\\PatchCord,App\\Models\\NetworkSwitch',
            'movable_id' => 'required|integer',
        ]);

        $movements = StockMovement::where('movable_type', $validated['movable_type'])
            ->where('movable_id', $validated['movable_id'])
            ->with(['fromSite', 'toSite', 'performedByUser'])
            ->orderBy('performed_at', 'desc')
            ->get();

        return response()->json([
            'asset' => [
                'type' => $validated['movable_type'],
                'id' => $validated['movable_id'],
            ],
            'movements' => $movements,
        ]);
    }

    /**
     * Get stock movement statistics.
     */
    public function statistics(Request $request)
    {
        $query = StockMovement::query();

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('performed_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('performed_at', '<=', $request->to_date);
        }

        $stats = [
            'total_movements' => (clone $query)->count(),
            'by_type' => (clone $query)
                ->select('movement_type', DB::raw('count(*) as count'))
                ->groupBy('movement_type')
                ->get()
                ->keyBy('movement_type')
                ->map(fn($item) => $item->count),
            'by_asset_type' => (clone $query)
                ->select('movable_type', DB::raw('count(*) as count'))
                ->groupBy('movable_type')
                ->get()
                ->keyBy('movable_type')
                ->map(fn($item) => $item->count),
            'recent_movements' => (clone $query)
                ->with(['movable', 'fromSite', 'toSite', 'performedByUser'])
                ->orderBy('performed_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json($stats);
    }
}
