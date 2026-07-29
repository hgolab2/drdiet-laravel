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
     *     summary="Get authenticated user visit report grouped by page URL",
     *     tags={"User Visit Logs"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=false,
     *         description="Start date for custom range",
     *         @OA\Schema(type="string", format="date", example="2026-07-01")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         description="End date for custom range",
     *         @OA\Schema(type="string", format="date", example="2026-07-31")
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Single selected date",
     *         @OA\Schema(type="string", format="date", example="2026-07-29")
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         description="Preset date range. Used when from/to and date are not provided.",
     *         @OA\Schema(
     *             type="string",
     *             enum={"today","yesterday","week","month","year"},
     *             default="today"
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Visit report returned successfully."
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error."
     *     )
     * )
     */
    public function report(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['super_admin', 'marketing'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'from' => ['nullable', 'date', 'required_with:to'],
            'to' => ['nullable', 'date', 'required_with:from', 'after_or_equal:from'],
            'date' => ['nullable', 'date'],
            'period' => ['nullable', 'string', 'in:today,yesterday,week,month,year'],
        ]);

        [$from, $to, $rangeType] = $this->visitReportDateRange($validated);

        $baseQuery = UserVisitLog::query()
            ->where('user_id', $request->user()->getKey())
            ->whereBetween('visited_at', [$from, $to]);

        $items = (clone $baseQuery)
            ->select('page_url')
            ->selectRaw('MIN(page_path) as page_path')
            ->selectRaw('MIN(page_title) as page_title')
            ->selectRaw('COUNT(*) as visits_count')
            ->selectRaw('MIN(visited_at) as first_visit_at')
            ->selectRaw('MAX(visited_at) as last_visit_at')
            ->groupBy('page_url')
            ->orderByDesc('visits_count')
            ->orderBy('page_url')
            ->get();

        return response()->json([
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'range_type' => $rangeType,
                'period' => $validated['period'] ?? null,
                'date' => isset($validated['date']) ? Carbon::parse($validated['date'])->toDateString() : null,
            ],
            'summary' => [
                'total_visits' => (clone $baseQuery)->count(),
                'unique_pages_count' => (clone $baseQuery)
                    ->distinct('page_url')
                    ->count('page_url'),
                'first_visit_at' => optional((clone $baseQuery)->oldest('visited_at')->first())->visited_at,
                'last_visit_at' => optional((clone $baseQuery)->latest('visited_at')->first())->visited_at,
            ],
            'items' => $items,
        ]);
    }

    /**
     * @param array{from?:string,to?:string,date?:string,period?:string} $validated
     *
     * @return array{0:Carbon,1:Carbon,2:string}
     */
    private function visitReportDateRange(array $validated): array
    {
        if (!empty($validated['from']) && !empty($validated['to'])) {
            return [
                Carbon::parse($validated['from'])->startOfDay(),
                Carbon::parse($validated['to'])->endOfDay(),
                'custom',
            ];
        }

        if (!empty($validated['date'])) {
            $date = Carbon::parse($validated['date']);

            return [
                $date->copy()->startOfDay(),
                $date->copy()->endOfDay(),
                'date',
            ];
        }

        return match ($validated['period'] ?? 'today') {
            'yesterday' => [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                'yesterday',
            ],
            'week' => [
                now()->startOfWeek(),
                now()->endOfWeek(),
                'week',
            ],
            'month' => [
                now()->startOfMonth(),
                now()->endOfMonth(),
                'month',
            ],
            'year' => [
                now()->startOfYear(),
                now()->endOfYear(),
                'year',
            ],
            default => [
                now()->startOfDay(),
                now()->endOfDay(),
                'today',
            ],
        };
    }
}
