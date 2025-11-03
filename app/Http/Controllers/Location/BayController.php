<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Bay;
use Illuminate\Http\Request;

class BayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Bay::query()->with('rack.site')->withCount('switches');

        // Filter by rack
        if ($request->has('rack_id')) {
            $query->where('rack_id', $request->rack_id);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'position');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $bays = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($bays);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rack_id' => 'required|exists:racks,id',
            'position' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
        ]);

        $bay = Bay::create($validated);
        $bay->load('rack.site');

        return response()->json([
            'message' => 'Bay created successfully',
            'bay' => $bay,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Bay $bay)
    {
        $bay->load(['rack.site', 'switches.switchModel']);

        return response()->json([
            'bay' => $bay,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bay $bay)
    {
        $validated = $request->validate([
            'rack_id' => 'sometimes|required|exists:racks,id',
            'position' => 'sometimes|required|integer|min:1',
            'name' => 'nullable|string|max:255',
        ]);

        $bay->update($validated);
        $bay->load('rack.site');

        return response()->json([
            'message' => 'Bay updated successfully',
            'bay' => $bay,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bay $bay)
    {
        // Check if bay has switches
        if ($bay->switches()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete bay with existing switches',
            ], 400);
        }

        $bay->delete();

        return response()->json([
            'message' => 'Bay deleted successfully',
        ]);
    }
}
