<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserLog\StoreUserVisitLogRequest;
use App\Models\UserVisitLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *     summary="Get authenticated user visit report",
     *     tags={"User Visit Logs"},
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         required=true,
     *         description="Start date",
     *         @OA\Schema(type="string", format="date", example="2026-07-01")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=true,
     *         description="End date",
     *         @OA\Schema(type="string", format="date", example="2026-07-31")
     *     ),
     *     @OA\Parameter(
     *         name="group_by",
     *         in="query",
     *         required=false,
     *         description="Group result by period",
     *         @OA\Schema(
     *             type="string",
     *             enum={"day","week","month","year"},
     *             default="day"
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
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'group_by' => ['nullable', 'string', 'in:day,week,month,year'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();
        $groupBy = $validated['group_by'] ?? 'day';

        $periodExpression = $this->visitReportPeriodExpression($groupBy);

        $baseQuery = UserVisitLog::query()
            ->where('user_id', $request->user()->getKey())
            ->whereBetween('visited_at', [$from, $to]);

        $items = (clone $baseQuery)
            ->selectRaw("$periodExpression as period")
            ->selectRaw('COUNT(*) as visits_count')
            ->selectRaw('COUNT(DISTINCT page_url) as unique_pages_count')
            ->selectRaw('MIN(visited_at) as first_visit_at')
            ->selectRaw('MAX(visited_at) as last_visit_at')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $topPages = (clone $baseQuery)
            ->select('page_url', 'page_path', 'page_title')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy('page_url', 'page_path', 'page_title')
            ->orderByDesc('visits_count')
            ->limit(10)
            ->get();

        return response()->json([
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'group_by' => $groupBy,
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
            'top_pages' => $topPages,
        ]);
    }

    private function visitReportPeriodExpression(string $groupBy): string
    {
        return match ($groupBy) {
            'week' => "DATE_FORMAT(visited_at, '%x-W%v')",
            'month' => "DATE_FORMAT(visited_at, '%Y-%m')",
            'year' => "DATE_FORMAT(visited_at, '%Y')",
            default => 'DATE(visited_at)',
        };
    }
}
