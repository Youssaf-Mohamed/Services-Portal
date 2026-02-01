<?php

namespace App\Http\Controllers\Api\IdCard;

use App\Http\Controllers\Controller;
use App\Http\Resources\IdCardTypeResource;
use App\Models\IdCardType;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminIdCardTypeController extends Controller
{
    /**
     * List all ID card types.
     */
    public function index(): JsonResponse
    {
        $types = IdCardType::ordered()->get();

        return ApiResponse::success(
            IdCardTypeResource::collection($types),
            'ID card types retrieved successfully'
        );
    }

    /**
     * Store a new ID card type.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:id_card_types,code',
            'name_ar' => 'required|string',
            'name_en' => 'required|string',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'requires_photo' => 'boolean',
            'requires_description' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $type = IdCardType::create($validated);

        return ApiResponse::success(
            new IdCardTypeResource($type),
            'ID card type created successfully'
        );
    }

    /**
     * Update the specified ID card type.
     */
    public function update(Request $request, IdCardType $type): JsonResponse
    {
        $validated = $request->validate([
            'name_ar' => 'sometimes|string',
            'name_en' => 'sometimes|string',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'fee' => 'sometimes|numeric|min:0',
            'requires_photo' => 'boolean',
            'requires_description' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $type->update($validated);

        return ApiResponse::success(
            new IdCardTypeResource($type),
            'ID card type updated successfully'
        );
    }

    /**
     * Remove the specified ID card type.
     */
    public function destroy(IdCardType $type): JsonResponse
    {
        // Check for dependencies if necessary (e.g., existing requests)
        if ($type->requests()->exists()) {
            return ApiResponse::error('Cannot delete type with existing requests. Deactivate it instead.', null, 409);
        }

        $type->delete();

        return ApiResponse::success(null, 'ID card type deleted successfully');
    }
}
