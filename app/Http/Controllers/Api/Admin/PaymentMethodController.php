<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $module = $request->query('module', 'transport');
        
        $methods = PaymentMethod::where('module', $module)->get();
        
        return response()->json([
            'success' => true,
            'data' => $methods
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'module' => 'sometimes|string|in:transport,id_card',
            'name' => 'required|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'active' => 'boolean',
        ]);

        // Default module to transport if not provided (for backward compatibility)
        if (!isset($validated['module'])) {
            $validated['module'] = 'transport';
        }

        $method = PaymentMethod::create($validated);

        return response()->json([
            'success' => true,
            'data' => $method,
            'message' => 'Payment method created successfully'
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, string $id)
    {
        $method = PaymentMethod::find($id);

        if (!$method) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'instructions' => 'nullable|string',
            'active' => 'boolean',
        ]);

        $method->update($validated);

        return response()->json([
            'success' => true,
            'data' => $method,
            'message' => 'Payment method updated successfully'
        ]);
    }

    public function destroy(string $id)
    {
        $method = PaymentMethod::find($id);

        if (!$method) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $method->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment method deleted successfully'
        ]);
    }
}
