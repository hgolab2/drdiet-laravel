<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceToken\DeleteDeviceTokenRequest;
use App\Http\Requests\DeviceToken\StoreDeviceTokenRequest;
use App\Services\Firebase\DeviceTokenService;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    public function __construct(private readonly DeviceTokenService $deviceTokenService)
    {
    }

    /**
     * Store or refresh the authenticated user's device token.
     */
    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        $deviceToken = $this->deviceTokenService->store($request->user(), $request->validated());

        return response()->json([
            'message' => 'Device token saved.',
            'data' => $deviceToken,
        ]);
    }

    /**
     * Deactivate the authenticated user's device token.
     */
    public function destroy(DeleteDeviceTokenRequest $request): JsonResponse
    {
        $this->deviceTokenService->deactivateForUser($request->user(), $request->validated('token'));

        return response()->json([
            'message' => 'Device token deactivated.',
        ]);
    }
}