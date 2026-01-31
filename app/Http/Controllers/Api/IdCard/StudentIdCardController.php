<?php

namespace App\Http\Controllers\Api\IdCard;

use App\Http\Controllers\Controller;
use App\Http\Resources\IdCardSettingResource;
use App\Http\Resources\IdCardTypeResource;
use App\Models\IdCardSetting;
use App\Models\IdCardType;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class StudentIdCardController extends Controller
{
    /**
     * List available ID card service types.
     */
    public function types(): JsonResponse
    {
        $types = IdCardType::active()
            ->ordered()
            ->get();

        return ApiResponse::success(
            IdCardTypeResource::collection($types),
            'ID card types retrieved successfully'
        );
    }

    /**
     * Get ID card service settings (payment destination).
     */
    public function settings(): JsonResponse
    {
        $settings = IdCardSetting::instance();

        if (!$settings) {
            return ApiResponse::error(
                'ID card service settings not configured',
                null,
                503
            );
        }

        return ApiResponse::success(
            new IdCardSettingResource($settings),
            'Settings retrieved successfully'
        );
    }
    /**
     * Get ID card payment methods.
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = \App\Models\PaymentMethod::where('module', 'id_card')
            ->active()
            ->get();

        return ApiResponse::success(
            $methods,
            'Payment methods retrieved successfully'
        );
    }
}
