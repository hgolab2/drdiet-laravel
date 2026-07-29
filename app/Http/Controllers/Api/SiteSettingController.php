<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSettingController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/settings",
     *     summary="Create or update a site setting",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Setting saved")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:150'],
            'value' => ['nullable'],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $setting = SiteSetting::query()->updateOrCreate(
            ['key' => $data['key']],
            [
                'value' => $data['value'] ?? null,
                'type' => $data['type'] ?? null,
                'description' => $data['description'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Setting saved successfully.',
            'data' => $setting,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/settings/{key}",
     *     summary="Update a site setting by key",
     *     tags={"Settings"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Setting updated")
     * )
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'value' => ['nullable'],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $setting = SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            $data
        );

        return response()->json([
            'message' => 'Setting updated successfully.',
            'data' => $setting,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/settings/{key}",
     *     summary="Get a site setting by key",
     *     tags={"Settings"},
     *     @OA\Parameter(name="key", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Setting value")
     * )
     */
    public function show(string $key): JsonResponse
    {
        $setting = SiteSetting::query()
            ->where('key', $key)
            ->firstOrFail();

        return response()->json([
            'key' => $setting->key,
            'value' => $setting->value,
            'type' => $setting->type,
            'description' => $setting->description,
        ]);
    }
}
