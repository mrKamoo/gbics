<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\Gbic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GbicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Gbic::query()->with(['fsComProduct', 'currentAssignment.switch']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by FS.com product
        if ($request->has('fs_com_product_id')) {
            $query->where('fs_com_product_id', $request->fs_com_product_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $gbics = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($gbics);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fs_com_product_id' => 'nullable|exists:fs_com_products,id',
            'serial_number' => 'required|string|max:255|unique:gbics',
            'barcode' => 'nullable|string|max:255|unique:gbics',
            'status' => 'nullable|in:in_stock,assigned,faulty,retired',
            'purchase_date' => 'nullable|date',
            'warranty_end' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Generate barcode if not provided
        if (empty($validated['barcode'])) {
            $validated['barcode'] = 'GBIC-' . strtoupper(Str::random(10));
        }

        $validated['status'] = $validated['status'] ?? 'in_stock';

        $gbic = Gbic::create($validated);
        $gbic->load('fsComProduct');

        return response()->json([
            'message' => 'GBIC created successfully',
            'gbic' => $gbic,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Gbic $gbic)
    {
        $gbic->load(['fsComProduct', 'assignments.switch', 'currentAssignment.switch', 'stockMovements', 'alerts']);

        return response()->json([
            'gbic' => $gbic,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Gbic $gbic)
    {
        $validated = $request->validate([
            'fs_com_product_id' => 'nullable|exists:fs_com_products,id',
            'serial_number' => 'sometimes|required|string|max:255|unique:gbics,serial_number,' . $gbic->id,
            'barcode' => 'nullable|string|max:255|unique:gbics,barcode,' . $gbic->id,
            'status' => 'nullable|in:in_stock,assigned,faulty,retired',
            'purchase_date' => 'nullable|date',
            'warranty_end' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $gbic->update($validated);
        $gbic->load('fsComProduct');

        return response()->json([
            'message' => 'GBIC updated successfully',
            'gbic' => $gbic,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gbic $gbic)
    {
        // Check if GBIC has active assignment
        if ($gbic->currentAssignment()->exists()) {
            return response()->json([
                'message' => 'Cannot delete GBIC with active assignment',
            ], 400);
        }

        $gbic->delete();

        return response()->json([
            'message' => 'GBIC deleted successfully',
        ]);
    }

    /**
     * Get GBIC assignment history.
     */
    public function history(Gbic $gbic)
    {
        $history = $gbic->assignments()
            ->with(['switch.switchModel', 'assignedByUser', 'unassignedByUser'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return response()->json([
            'gbic' => [
                'id' => $gbic->id,
                'serial_number' => $gbic->serial_number,
                'barcode' => $gbic->barcode,
            ],
            'history' => $history,
        ]);
    }

    /**
     * Find GBIC by barcode.
     */
    public function findByBarcode(string $barcode)
    {
        $gbic = Gbic::where('barcode', $barcode)
            ->with(['fsComProduct', 'currentAssignment.switch'])
            ->first();

        if (!$gbic) {
            return response()->json([
                'message' => 'GBIC not found',
            ], 404);
        }

        return response()->json([
            'gbic' => $gbic,
        ]);
    }

    /**
     * Bulk import GBICs.
     */
    public function bulkImport(Request $request)
    {
        $validated = $request->validate([
            'gbics' => 'required|array',
            'gbics.*.fs_com_product_id' => 'nullable|exists:fs_com_products,id',
            'gbics.*.serial_number' => 'required|string|max:255',
            'gbics.*.purchase_date' => 'nullable|date',
            'gbics.*.warranty_end' => 'nullable|date',
        ]);

        $created = [];
        $errors = [];

        foreach ($validated['gbics'] as $index => $gbicData) {
            try {
                // Check if serial number already exists
                if (Gbic::where('serial_number', $gbicData['serial_number'])->exists()) {
                    $errors[] = [
                        'index' => $index,
                        'serial_number' => $gbicData['serial_number'],
                        'error' => 'Serial number already exists',
                    ];
                    continue;
                }

                // Generate unique barcode
                $gbicData['barcode'] = 'GBIC-' . strtoupper(Str::random(10));
                $gbicData['status'] = 'in_stock';

                $gbic = Gbic::create($gbicData);
                $created[] = $gbic;
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'serial_number' => $gbicData['serial_number'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => count($created) . ' GBICs imported successfully',
            'created' => count($created),
            'errors' => count($errors),
            'details' => [
                'created_gbics' => $created,
                'errors' => $errors,
            ],
        ], 201);
    }
}
