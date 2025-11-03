<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Gbic;
use App\Models\NetworkSwitch;
use App\Models\PatchCord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Assignment::query()
            ->with(['assignable', 'switch.switchModel', 'assignedByUser', 'unassignedByUser']);

        // Filter by active assignments
        if ($request->has('active') && $request->active) {
            $query->active();
        }

        // Filter by switch
        if ($request->has('switch_id')) {
            $query->where('switch_id', $request->switch_id);
        }

        // Filter by assignable type
        if ($request->has('assignable_type')) {
            $query->where('assignable_type', $request->assignable_type);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'assigned_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $assignments = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($assignments);
    }

    /**
     * Store a newly created resource in storage (Create assignment).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'assignable_type' => 'required|in:App\\Models\\Gbic,App\\Models\\PatchCord',
            'assignable_id' => 'required|integer',
            'switch_id' => 'required|exists:switches,id',
            'port_number' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        // Check if assignable exists
        $assignableClass = $validated['assignable_type'];
        $assignable = $assignableClass::find($validated['assignable_id']);

        if (!$assignable) {
            return response()->json([
                'message' => 'Asset not found',
            ], 404);
        }

        // Check if asset is available (not already assigned)
        if ($assignable->currentAssignment()->exists()) {
            return response()->json([
                'message' => 'Asset is already assigned',
            ], 400);
        }

        // Check if port is available
        $switch = NetworkSwitch::find($validated['switch_id']);
        $portInUse = $switch->assignments()
            ->where('port_number', $validated['port_number'])
            ->active()
            ->exists();

        if ($portInUse) {
            return response()->json([
                'message' => 'Port is already in use',
            ], 400);
        }

        // Check if port number is valid for switch model
        if ($validated['port_number'] > $switch->switchModel->port_count) {
            return response()->json([
                'message' => 'Invalid port number for this switch model',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Create assignment
            $assignment = Assignment::create([
                'assignable_type' => $validated['assignable_type'],
                'assignable_id' => $validated['assignable_id'],
                'switch_id' => $validated['switch_id'],
                'port_number' => $validated['port_number'],
                'assigned_by' => $request->user()->id,
                'assigned_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update asset status
            $assignable->update(['status' => 'assigned']);

            DB::commit();

            $assignment->load(['assignable', 'switch.switchModel', 'assignedByUser']);

            return response()->json([
                'message' => 'Asset assigned successfully',
                'assignment' => $assignment,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Assignment $assignment)
    {
        $assignment->load([
            'assignable',
            'switch.switchModel.switches',
            'switch.bay.rack.site',
            'assignedByUser',
            'unassignedByUser'
        ]);

        return response()->json([
            'assignment' => $assignment,
        ]);
    }

    /**
     * Remove the specified resource from storage (Unassign).
     */
    public function destroy(Request $request, Assignment $assignment)
    {
        // Check if already unassigned
        if ($assignment->unassigned_at !== null) {
            return response()->json([
                'message' => 'Assignment is already terminated',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Update assignment
            $assignment->update([
                'unassigned_at' => now(),
                'unassigned_by' => $request->user()->id,
            ]);

            // Update asset status
            $assignment->assignable->update(['status' => 'in_stock']);

            DB::commit();

            $assignment->load(['assignable', 'switch', 'unassignedByUser']);

            return response()->json([
                'message' => 'Asset unassigned successfully',
                'assignment' => $assignment,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to unassign asset',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all assignments for a specific switch.
     */
    public function bySwitch(NetworkSwitch $switch)
    {
        $assignments = $switch->assignments()
            ->with(['assignable', 'assignedByUser', 'unassignedByUser'])
            ->orderBy('port_number', 'asc')
            ->orderBy('assigned_at', 'desc')
            ->get();

        return response()->json([
            'switch' => [
                'id' => $switch->id,
                'serial_number' => $switch->serial_number,
                'hostname' => $switch->hostname,
                'model' => $switch->switchModel->manufacturer . ' ' . $switch->switchModel->model,
            ],
            'assignments' => $assignments,
        ]);
    }

    /**
     * Get assignment history (all assignments, including unassigned).
     */
    public function history(Request $request)
    {
        $query = Assignment::query()
            ->with(['assignable', 'switch.switchModel', 'assignedByUser', 'unassignedByUser'])
            ->orderBy('assigned_at', 'desc');

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('assigned_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('assigned_at', '<=', $request->to_date);
        }

        // Paginate
        $history = $query->paginate($request->get('per_page', 50));

        return response()->json($history);
    }
}
