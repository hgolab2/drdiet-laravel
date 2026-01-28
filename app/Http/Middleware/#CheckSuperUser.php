<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuperUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user || !$user->is_superuser) {
            return response()->json([
                'message' => 'دسترسی غیرمجاز'
            ], 401);
        }
        return $next($request);
    }
}
