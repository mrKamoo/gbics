<?php

namespace App\Http\Controllers\Location;

use App\Http\Controllers\Controller;
use App\Models\Rack;
use Illuminate\Http\Request;

class RackController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Rack::query()->with('site')->withCount('bays');

        // Filter by site
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $racks = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($racks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'units' => 'required|integer|min:1|max:100',
        ]);

        $rack = Rack::create($validated);
        $rack->load('site');

        return response()->json([
            'message' => 'Rack created successfully',
            'rack' => $rack,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Rack $rack)
    {
        $rack->load(['site', 'bays.switches']);

        return response()->json([
            'rack' => $rack,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Rack $rack)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|required|exists:sites,id',
            'name' => 'sometimes|required|string|max:255',
            'location' => 'nullable|string|max:255',
            'units' => 'sometimes|required|integer|min:1|max:100',
        ]);

        $rack->update($validated);
        $rack->load('site');

        return response()->json([
            'message' => 'Rack updated successfully',
            'rack' => $rack,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Rack $rack)
    {
        // Check if rack has bays
        if ($rack->bays()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete rack with existing bays',
            ], 400);
        }

        $rack->delete();

        return response()->json([
            'message' => 'Rack deleted successfully',
        ]);
    }
}
