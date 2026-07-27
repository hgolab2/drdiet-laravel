<?php

namespace App\Services\Firebase;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DeviceTokenService
{
    /**
     * Store or refresh a device token for the authenticated user.
     *
     * @param array{token:string, platform:string, browser?:string|null, device_name?:string|null} $data
     */
    public function store(User $user, array $data): DeviceToken
    {
        return DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $user->getKey(),
                'platform' => $data['platform'],
                'browser' => $data['browser'] ?? null,
                'device_name' => $data['device_name'] ?? null,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Deactivate a token owned by the authenticated user.
     */
    public function deactivateForUser(User $user, string $token): bool
    {
        return DeviceToken::query()
            ->where('token', $token)
            ->where('user_id', $user->getKey())
            ->update(['is_active' => false, 'last_used_at' => now()]) > 0;
    }

    /**
     * Deactivate tokens Firebase reports as invalid.
     *
     * @param array<int, string> $tokens
     */
    public function deactivateTokens(array $tokens): void
    {
        DeviceToken::query()
            ->whereIn('token', array_values(array_unique($tokens)))
            ->update(['is_active' => false, 'last_used_at' => now()]);
    }

    /**
     * @return Collection<int, DeviceToken>
     */
    public function activeForUser(User $user): Collection
    {
        return $user->deviceTokens()->where('is_active', true)->get();
    }
}