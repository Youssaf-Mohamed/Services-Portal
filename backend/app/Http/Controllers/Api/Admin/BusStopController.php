<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusStop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BusStopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stops = BusStop::withCount('routes')->orderBy('name_en')->get();
        return response()->json([
            'success' => true,
            'data' => $stops
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $stop = BusStop::create($validated);

        return response()->json([
            'success' => true,
            'data' => $stop,
            'message' => 'Bus stop created successfully'
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $stop = BusStop::find($id);

        if (!$stop) {
            return response()->json([
                'success' => false,
                'message' => 'Bus stop not found'
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'data' => $stop
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $stop = BusStop::find($id);

        if (!$stop) {
            return response()->json([
                'success' => false,
                'message' => 'Bus stop not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'name_ar' => 'sometimes|required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        $stop->update($validated);

        return response()->json([
            'success' => true,
            'data' => $stop,
            'message' => 'Bus stop updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $stop = BusStop::find($id);

        if (!$stop) {
            return response()->json([
                'success' => false,
                'message' => 'Bus stop not found'
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if stop is attached to any routes
        if ($stop->routes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete stop because it is assigned to one or more routes.'
            ], Response::HTTP_CONFLICT);
        }

        $stop->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bus stop deleted successfully'
        ]);
    }
}
