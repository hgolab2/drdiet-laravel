<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * @OA\Get(
     *     path="/api/locations/countries",
     *     summary="لیست کشورها",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string")
     *         ))
     *     )
     * )
     */
    public function countries()
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'sales_expert' , 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json(Country::select('id', 'name')->get());
    }

    /**
     * @OA\Get(
     *     path="/api/locations/states",
     *     summary="لیست استان‌ها بر اساس کشور",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="country_id",
     *         in="query",
     *         required=true,
     *         description="شناسه کشور",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string")
     *         ))
     *     )
     * )
     */
    public function states(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $request->validate(['country_id' => 'required|integer|exists:countries,id']);
        return response()->json(State::where('country_id', $request->country_id)->select('id', 'name')->get());
    }

    /**
     * @OA\Get(
     *     path="/api/locations/cities",
     *     summary="لیست شهرها بر اساس استان",
     *     tags={"Locations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="state_id",
     *         in="query",
     *         required=true,
     *         description="شناسه استان",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="موفق",
     *         @OA\JsonContent(type="array", @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="name", type="string")
     *         ))
     *     )
     * )
     */
    public function cities(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'support'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $request->validate(['state_id' => 'required|integer|exists:states,id']);
        return response()->json(City::where('state_id', $request->state_id)->select('id', 'name')->get());
    }
}
