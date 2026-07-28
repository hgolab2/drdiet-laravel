<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserVisitLog;
use Illuminate\Http\Request;

class UserVisitLogService
{
    /**
     * @param array{page_url:string,page_path?:string|null,page_title?:string|null,referrer_url?:string|null,metadata?:array|null,visited_at?:string|null} $data
     */
    public function store(?User $user, array $data, Request $request): UserVisitLog
    {
        return UserVisitLog::query()->create([
            'user_id' => $user?->getKey(),
            'page_url' => $data['page_url'],
            'page_path' => $data['page_path'] ?? $this->pathFromUrl($data['page_url']),
            'page_title' => $data['page_title'] ?? null,
            'referrer_url' => $data['referrer_url'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $data['metadata'] ?? null,
            'visited_at' => $data['visited_at'] ?? now(),
        ]);
    }

    private function pathFromUrl(string $url): ?string
    {
        return parse_url($url, PHP_URL_PATH) ?: null;
    }
}
