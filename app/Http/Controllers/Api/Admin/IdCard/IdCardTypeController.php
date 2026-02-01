<?php

namespace App\Http\Controllers\Api\Admin\IdCard;

use App\Http\Controllers\Controller;
use App\Models\IdCardType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class IdCardTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $types = IdCardType::ordered()->get();
        return response()->json($types);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:id_card_types,code'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'requires_photo' => ['boolean'],
            'requires_description' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $type = IdCardType::create($validated);

        return response()->json($type, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(IdCardType $type): JsonResponse
    {
        return response()->json($type);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IdCardType $type): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('id_card_types')->ignore($type->id)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'fee' => ['required', 'numeric', 'min:0'],
            'requires_photo' => ['boolean'],
            'requires_description' => ['boolean'],
            'active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $type->update($validated);

        return response()->json($type);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IdCardType $type): JsonResponse
    {
        // Check if there are requests for this type before deleting?
        // Ideally yes, but for now standard delete
        if ($type->requests()->exists()) {
             return response()->json(['message' => 'Cannot delete type with existing requests. Deactivate it instead.'], 422);
        }

        $type->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle active status
     */
    public function toggleActive(IdCardType $idCardType): JsonResponse
    {
        $idCardType->update(['active' => !$idCardType->active]);
        return response()->json($idCardType);
    }
}
