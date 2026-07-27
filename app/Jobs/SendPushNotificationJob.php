<?php

namespace App\Jobs;

use App\Services\Firebase\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, string> $tokens
     * @param array<string, mixed> $payload
     */
    public function __construct(private readonly array $tokens, private readonly array $payload)
    {
    }

    /**
     * Send the queued push notification.
     */
    public function handle(FirebaseService $firebaseService): void
    {
        $result = $firebaseService->sendToTokens($this->tokens, $this->payload);

        if (($result['failed'] ?? 0) > 0) {
            Log::warning('Queued Firebase push notification completed with failures.', $result);
        }
    }
}