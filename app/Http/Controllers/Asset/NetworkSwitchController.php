<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\NetworkSwitch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NetworkSwitchController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = NetworkSwitch::query()
            ->with(['switchModel', 'bay.rack.site'])
            ->withCount('assignments');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by switch model
        if ($request->has('switch_model_id')) {
            $query->where('switch_model_id', $request->switch_model_id);
        }

        // Filter by bay
        if ($request->has('bay_id')) {
            $query->where('bay_id', $request->bay_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('asset_tag', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('hostname', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $switches = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($switches);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'switch_model_id' => 'required|exists:switch_models,id',
            'bay_id' => 'nullable|exists:bays,id',
            'serial_number' => 'required|string|max:255|unique:switches',
            'asset_tag' => 'nullable|string|max:255|unique:switches',
            'barcode' => 'nullable|string|max:255|unique:switches',
            'hostname' => 'nullable|string|max:255',
            'status' => 'nullable|in:in_stock,deployed,maintenance,retired',
            'purchase_date' => 'nullable|date',
            'warranty_end' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        // Generate barcode if not provided
        if (empty($validated['barcode'])) {
            $validated['barcode'] = 'SW-' . strtoupper(Str::random(10));
        }

        $validated['status'] = $validated['status'] ?? 'in_stock';

        $switch = NetworkSwitch::create($validated);
        $switch->load(['switchModel', 'bay.rack.site']);

        return response()->json([
            'message' => 'Switch created successfully',
            'switch' => $switch,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(NetworkSwitch $switch)
    {
        $switch->load([
            'switchModel',
            'bay.rack.site',
            'assignments.assignable',
            'stockMovements',
            'alerts'
        ]);

        return response()->json([
            'switch' => $switch,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NetworkSwitch $switch)
    {
        $validated = $request->validate([
            'switch_model_id' => 'sometimes|required|exists:switch_models,id',
            'bay_id' => 'nullable|exists:bays,id',
            'serial_number' => 'sometimes|required|string|max:255|unique:switches,serial_number,' . $switch->id,
            'asset_tag' => 'nullable|string|max:255|unique:switches,asset_tag,' . $switch->id,
            'barcode' => 'nullable|string|max:255|unique:switches,barcode,' . $switch->id,
            'hostname' => 'nullable|string|max:255',
            'status' => 'nullable|in:in_stock,deployed,maintenance,retired',
            'purchase_date' => 'nullable|date',
            'warranty_end' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $switch->update($validated);
        $switch->load(['switchModel', 'bay.rack.site']);

        return response()->json([
            'message' => 'Switch updated successfully',
            'switch' => $switch,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NetworkSwitch $switch)
    {
        // Check if switch has active assignments
        if ($switch->assignments()->active()->exists()) {
            return response()->json([
                'message' => 'Cannot delete switch with active assignments',
            ], 400);
        }

        $switch->delete();

        return response()->json([
            'message' => 'Switch deleted successfully',
        ]);
    }

    /**
     * Get switch ports with their assignments.
     */
    public function ports(NetworkSwitch $switch)
    {
        $switch->load(['switchModel', 'assignments.assignable']);

        $portCount = $switch->switchModel->port_count;
        $ports = [];

        for ($i = 1; $i <= $portCount; $i++) {
            $assignment = $switch->assignments()
                ->where('port_number', $i)
                ->active()
                ->with('assignable')
                ->first();

            $ports[] = [
                'port_number' => $i,
                'is_assigned' => $assignment !== null,
                'assignment' => $assignment,
            ];
        }

        return response()->json([
            'switch' => [
                'id' => $switch->id,
                'serial_number' => $switch->serial_number,
                'hostname' => $switch->hostname,
                'model' => $switch->switchModel->manufacturer . ' ' . $switch->switchModel->model,
            ],
            'ports' => $ports,
        ]);
    }

    /**
     * Find switch by barcode.
     */
    public function findByBarcode(string $barcode)
    {
        $switch = NetworkSwitch::where('barcode', $barcode)
            ->with(['switchModel', 'bay.rack.site', 'assignments.assignable'])
            ->first();

        if (!$switch) {
            return response()->json([
                'message' => 'Switch not found',
            ], 404);
        }

        return response()->json([
            'switch' => $switch,
        ]);
    }

    /**
     * Get switch assignment history.
     */
    public function history(NetworkSwitch $switch)
    {
        $history = $switch->assignments()
            ->with(['assignable', 'assignedByUser', 'unassignedByUser'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return response()->json([
            'switch' => [
                'id' => $switch->id,
                'serial_number' => $switch->serial_number,
                'hostname' => $switch->hostname,
            ],
            'history' => $history,
        ]);
    }
}
