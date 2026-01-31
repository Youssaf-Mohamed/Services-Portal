<?php

namespace App\Http\Controllers\Api\Admin\IdCard;

use App\Http\Controllers\Controller;
use App\Http\Requests\IdCard\UpdateIdCardSettingRequest;
use App\Http\Resources\IdCardSettingResource;
use App\Models\IdCardSetting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class IdCardSettingController extends Controller
{
    /**
     * Get the current ID card settings.
     */
    public function show(): JsonResponse
    {
        $settings = IdCardSetting::instance();

        if (!$settings) {
            return ApiResponse::error(
                'ID card settings not configured. Please run the seeder.',
                null,
                503
            );
        }

        return ApiResponse::success([
            'settings' => new IdCardSettingResource($settings),
            'updated_at' => $settings->updated_at?->toIso8601String(),
            'updated_by' => $settings->updater ? [
                'id' => $settings->updater->id,
                'name' => $settings->updater->name,
            ] : null,
        ]);
    }

    /**
     * Update the ID card settings.
     */
    public function update(UpdateIdCardSettingRequest $request): JsonResponse
    {
        $settings = IdCardSetting::instance();

        if (!$settings) {
            // Create if doesn't exist
            $settings = new IdCardSetting();
        }

        $settings->fill([
            'payment_account_number' => $request->payment_account_number,
            'payment_account_name' => $request->payment_account_name,
            'payment_instructions' => $request->payment_instructions,
            'service_enabled' => $request->service_enabled,
            'updated_by' => auth()->id(),
        ]);

        $settings->save();

        return ApiResponse::success([
            'settings' => new IdCardSettingResource($settings),
            'updated_at' => $settings->updated_at->toIso8601String(),
        ], 'Settings updated successfully');
    }
}
