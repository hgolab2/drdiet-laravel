<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPerformanceNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPerformanceNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @OA\Get(
     *     path="/api/user-performance-notes",
     *     summary="List all user performance notes",
     *     description="Returns all user performance notes for super admin users. Supports filtering by target user, creator, and date range.",
     *     tags={"User Performance Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user_id", in="query", required=false, description="Target user id", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="created_by", in="query", required=false, description="Creator user id", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="from", in="query", required=false, description="Start creation date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="to", in="query", required=false, description="End creation date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="pagesize", in="query", required=false, description="Items per page", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Performance notes returned successfully"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();
        if (!$authUser || !$authUser->hasAnyRole(['super_admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:diet_users,id'],
            'created_by' => ['nullable', 'integer', 'exists:diet_users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'pagesize' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = UserPerformanceNote::query()
            ->with(['user:id,first_name,last_name,phone', 'creator:id,first_name,last_name,phone']);

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }
        if (!empty($validated['created_by'])) {
            $query->where('created_by', $validated['created_by']);
        }
        if (!empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        $pageSize = (int)($validated['pagesize'] ?? 20);
        $totalCount = (clone $query)->count();
        $items = $query->latest('id')->paginate($pageSize);

        return response()->json([
            'result' => $items->items(),
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{user}/performance-notes",
     *     summary="List performance notes for a user",
     *     description="Returns performance notes registered for the selected user.",
     *     tags={"User Performance Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, description="Target user id", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="pagesize", in="query", required=false, description="Items per page", @OA\Schema(type="integer", default=20)),
     *     @OA\Response(response=200, description="Performance notes returned successfully"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function userNotes(Request $request, int $user): JsonResponse
    {
        $authUser = $request->user();
        if (!$this->canManageNotes($authUser)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (!User::whereKey($user)->exists()) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $validated = $request->validate([
            'pagesize' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $pageSize = (int)($validated['pagesize'] ?? 20);
        $query = UserPerformanceNote::query()
            ->where('user_id', $user)
            ->with(['creator:id,first_name,last_name,phone']);

        $totalCount = (clone $query)->count();
        $items = $query->latest('id')->paginate($pageSize);

        return response()->json([
            'result' => $items->items(),
            'totalCount' => $totalCount,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/users/{user}/performance-notes",
     *     summary="Create a performance note for a user",
     *     description="Creates a performance note for the selected user and stores the creator and creation time.",
     *     tags={"User Performance Notes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="user", in="path", required=true, description="Target user id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"description"},
     *             @OA\Property(property="description", type="string", maxLength=5000, example="User performance note text")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Performance note created successfully"),
     *     @OA\Response(response=403, description="Unauthorized"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request, int $user): JsonResponse
    {
        $authUser = $request->user();
        if (!$this->canManageNotes($authUser)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (!User::whereKey($user)->exists()) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $note = UserPerformanceNote::create([
            'user_id' => $user,
            'created_by' => $authUser->getKey(),
            'description' => $validated['description'],
        ]);

        $note->load(['user:id,first_name,last_name,phone', 'creator:id,first_name,last_name,phone']);

        return response()->json([
            'message' => 'Performance note created.',
            'data' => $note,
        ], 201);
    }

    private function canManageNotes(?User $user): bool
    {
        return $user && $user->hasAnyRole(['super_admin', 'nutrition_expert', 'sales_expert', 'support']);
    }
}