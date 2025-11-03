<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use App\Models\SwitchModel;
use Illuminate\Http\Request;

class SwitchModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = SwitchModel::query()->withCount('switches');

        // Filter by manufacturer
        if ($request->has('manufacturer')) {
            $query->where('manufacturer', $request->manufacturer);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('manufacturer', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'manufacturer');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate or get all
        $models = $request->has('per_page')
            ? $query->paginate($request->per_page)
            : $query->get();

        return response()->json($models);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'manufacturer' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'port_count' => 'required|integer|min:1',
            'port_types' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $switchModel = SwitchModel::create($validated);

        return response()->json([
            'message' => 'Switch model created successfully',
            'switch_model' => $switchModel,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SwitchModel $switchModel)
    {
        $switchModel->load('switches.bay.rack.site');

        return response()->json([
            'switch_model' => $switchModel,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SwitchModel $switchModel)
    {
        $validated = $request->validate([
            'manufacturer' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'port_count' => 'sometimes|required|integer|min:1',
            'port_types' => 'nullable|array',
            'description' => 'nullable|string',
        ]);

        $switchModel->update($validated);

        return response()->json([
            'message' => 'Switch model updated successfully',
            'switch_model' => $switchModel,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SwitchModel $switchModel)
    {
        // Check if model has switches
        if ($switchModel->switches()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete switch model with existing switches',
            ], 400);
        }

        $switchModel->delete();

        return response()->json([
            'message' => 'Switch model deleted successfully',
        ]);
    }
}
