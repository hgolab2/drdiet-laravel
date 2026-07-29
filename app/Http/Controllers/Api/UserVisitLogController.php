<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLog\StoreUserVisitLogRequest;
use App\Models\UserVisitLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserVisitLogController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/user-visit-logs",
     *     operationId="storeUserVisitLog",
     *     tags={"User Visit Logs"},
     *     summary="Store page visit log",
     *     description="Stores a page visit log for both authenticated and guest users.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"page_url"},
     *
     *             @OA\Property(
     *                 property="page_url",
     *                 type="string",
     *                 maxLength=2048,
     *                 example="https://drdietapp.com/dashboard"
     *             ),
     *
     *             @OA\Property(
     *                 property="page_path",
     *                 type="string",
     *                 nullable=true,
     *                 maxLength=1024,
     *                 example="/dashboard"
     *             ),
     *
     *             @OA\Property(
     *                 property="page_title",
     *                 type="string",
     *                 nullable=true,
     *                 maxLength=255,
     *                 example="Dashboard"
     *             ),
     *
     *             @OA\Property(
     *                 property="referrer_url",
     *                 type="string",
     *                 nullable=true,
     *                 maxLength=2048,
     *                 example="https://google.com"
     *             ),
     *
     *             @OA\Property(
     *                 property="metadata",
     *                 type="object",
     *                 nullable=true,
     *                 example={
     *                     "device":"desktop",
     *                     "browser":"Chrome",
     *                     "os":"Windows",
     *                     "language":"fa"
     *                 }
     *             ),
     *
     *             @OA\Property(
     *                 property="visited_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 example="2026-07-29T10:30:00Z"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Visit log saved successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="User visit log saved."
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", nullable=true, example=15),
     *                 @OA\Property(property="page_url", type="string"),
     *                 @OA\Property(property="page_path", type="string", nullable=true),
     *                 @OA\Property(property="page_title", type="string", nullable=true),
     *                 @OA\Property(property="referrer_url", type="string", nullable=true),
     *                 @OA\Property(property="ip_address", type="string", example="192.168.1.15"),
     *                 @OA\Property(property="user_agent", type="string", example="Mozilla/5.0"),
     *                 @OA\Property(property="metadata", type="object"),
     *                 @OA\Property(property="visited_at", type="string", format="date-time"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error."
     *     )
     * )
     */
    public function store(StoreUserVisitLogRequest $request): JsonResponse
    {
        $data = $request->validated();

        $visitLog = UserVisitLog::create([
            'user_id'      => auth('api')->id(),
            'page_url'     => $data['page_url'],
            'page_path'    => $data['page_path'] ?? null,
            'page_title'   => $data['page_title'] ?? null,
            'referrer_url' => $data['referrer_url'] ?? null,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'metadata'     => $data['metadata'] ?? null,
            'visited_at'   => $data['visited_at'] ?? now(),
        ]);

        return response()->json([
            'message' => 'User visit log saved.',
            'data' => $visitLog,
        ], 201);
    }

    /**
 * @OA\Get(
 *     path="/api/user-visit-logs/report",
 *     operationId="userVisitLogReport",
 *     tags={"User Visit Logs"},
 *     summary="Page visit report",
 *     description="Returns page visit statistics grouped by page_url.",
 *     security={{"bearerAuth":{}}},
 *
 *     @OA\Parameter(
 *         name="period",
 *         in="query",
 *         required=true,
 *         description="Date period",
 *         @OA\Schema(
 *             type="string",
 *             enum={"today","yesterday","week","month","year","custom"},
 *             example="today"
 *         )
 *     ),
 *
 *     @OA\Parameter(
 *         name="from",
 *         in="query",
 *         required=false,
 *         description="Required when period=custom",
 *         @OA\Schema(
 *             type="string",
 *             format="date",
 *             example="2026-07-01"
 *         )
 *     ),
 *
 *     @OA\Parameter(
 *         name="to",
 *         in="query",
 *         required=false,
 *         description="Required when period=custom",
 *         @OA\Schema(
 *             type="string",
 *             format="date",
 *             example="2026-07-31"
 *         )
 *     ),
 *
 *     @OA\Response(
 *         response=200,
 *         description="Visit report."
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized."
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error."
 *     )
 * )
 */
    public function report(Request $request): JsonResponse
{
    $validated = $request->validate([
        'period' => 'required|in:today,yesterday,week,month,year,custom',
        'from' => 'nullable|date',
        'to' => 'nullable|date|after_or_equal:from',
    ]);

    switch ($validated['period']) {

        case 'today':
            $from = now()->startOfDay();
            $to = now()->endOfDay();
            break;

        case 'yesterday':
            $from = now()->subDay()->startOfDay();
            $to = now()->subDay()->endOfDay();
            break;

        case 'week':
            $from = now()->startOfWeek();
            $to = now()->endOfWeek();
            break;

        case 'month':
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
            break;

        case 'year':
            $from = now()->startOfYear();
            $to = now()->endOfYear();
            break;

        default:

            if (empty($validated['from']) || empty($validated['to'])) {
                return response()->json([
                    'message' => 'from and to are required when period is custom.'
                ], 422);
            }

            $from = Carbon::parse($validated['from'])->startOfDay();
            $to = Carbon::parse($validated['to'])->endOfDay();
    }

    $query = UserVisitLog::query()
        ->whereBetween('created_at', [$from, $to]);

    $pages = (clone $query)
        ->select('page_url', 'page_title', 'page_path')
        ->selectRaw('COUNT(*) as visits_count')
        ->selectRaw('MAX(created_at) as last_visit_at')
        ->groupBy('page_url', 'page_title', 'page_path')
        ->orderByDesc('visits_count')
        ->get();

    return response()->json([
        'filters' => [
            'period' => $validated['period'],
            'from' => $from,
            'to' => $to,
        ],

        'summary' => [
            'total_visits' => (clone $query)->count(),
            'unique_pages' => $pages->count(),
        ],

        'pages' => $pages,
    ]);
}
}
