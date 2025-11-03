<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\PatchCord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PatchCordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PatchCord::query()->with(['fsComProduct']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by fiber type
        if ($request->has('fiber_type')) {
            $query->where('fiber_type', $request->fiber_type);
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
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('connector_type_a', 'like', "%{$search}%")
                  ->orWhere('connector_type_b', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $patchCords = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json([
            'data' => $patchCords
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fs_com_product_id' => 'nullable|exists:fs_com_products,id',
            'serial_number' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:patch_cords',
            'length' => 'required|numeric|min:0',
            'connector_type_a' => 'required|string|max:255',
            'connector_type_b' => 'required|string|max:255',
            'fiber_type' => 'required|in:single_mode,multi_mode',
            'status' => 'nullable|in:in_stock,deployed,faulty,retired',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Generate barcode if not provided
        if (empty($validated['barcode'])) {
            $validated['barcode'] = 'PC-' . strtoupper(Str::random(10));
        }

        $validated['status'] = $validated['status'] ?? 'in_stock';

        $patchCord = PatchCord::create($validated);
        $patchCord->load('fsComProduct');

        return response()->json([
            'message' => 'Patch cord created successfully',
            'patch_cord' => $patchCord,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PatchCord $patchCord)
    {
        $patchCord->load([
            'fsComProduct',
            'assignments.switch',
            'currentAssignment.switch',
            'stockMovements',
            'alerts'
        ]);

        return response()->json([
            'patch_cord' => $patchCord,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PatchCord $patchCord)
    {
        $validated = $request->validate([
            'fs_com_product_id' => 'nullable|exists:fs_com_products,id',
            'serial_number' => 'nullable|string|max:255',
            'barcode' => 'nullable|string|max:255|unique:patch_cords,barcode,' . $patchCord->id,
            'length' => 'sometimes|required|numeric|min:0',
            'connector_type_a' => 'sometimes|required|string|max:255',
            'connector_type_b' => 'sometimes|required|string|max:255',
            'fiber_type' => 'sometimes|required|in:single_mode,multi_mode',
            'status' => 'nullable|in:in_stock,deployed,faulty,retired',
            'purchase_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $patchCord->update($validated);
        $patchCord->load('fsComProduct');

        return response()->json([
            'message' => 'Patch cord updated successfully',
            'patch_cord' => $patchCord,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatchCord $patchCord)
    {
        // Check if patch cord has active assignment
        if ($patchCord->currentAssignment()->exists()) {
            return response()->json([
                'message' => 'Cannot delete patch cord with active assignment',
            ], 400);
        }

        $patchCord->delete();

        return response()->json([
            'message' => 'Patch cord deleted successfully',
        ]);
    }

    /**
     * Get patch cord assignment history.
     */
    public function history(PatchCord $patchCord)
    {
        $history = $patchCord->assignments()
            ->with(['switch.switchModel', 'assignedByUser', 'unassignedByUser'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return response()->json([
            'patch_cord' => [
                'id' => $patchCord->id,
                'serial_number' => $patchCord->serial_number,
                'barcode' => $patchCord->barcode,
                'length' => $patchCord->length,
                'fiber_type' => $patchCord->fiber_type,
            ],
            'history' => $history,
        ]);
    }

    /**
     * Find patch cord by barcode.
     */
    public function findByBarcode(string $barcode)
    {
        $patchCord = PatchCord::where('barcode', $barcode)
            ->with(['fsComProduct', 'currentAssignment.switch'])
            ->first();

        if (!$patchCord) {
            return response()->json([
                'message' => 'Patch cord not found',
            ], 404);
        }

        return response()->json([
            'patch_cord' => $patchCord,
        ]);
    }
}
