<?php

namespace App\Services\Firebase;

use App\Exceptions\Firebase\FirebaseAuthenticationException;
use App\Exceptions\Firebase\FirebaseConfigurationException;
use App\Exceptions\Firebase\FirebaseMessagingException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(private readonly DeviceTokenService $deviceTokenService)
    {
    }

    /**
     * Generate or return a cached OAuth2 access token.
     */
    public function accessToken(): string
    {
        return Cache::remember($this->cacheKey(), now()->addMinutes(50), function (): string {
            $credentials = $this->credentials();
            $now = time();
            $assertion = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR))
                . '.'
                . $this->base64UrlEncode(json_encode([
                    'iss' => $credentials['client_email'],
                    'scope' => self::SCOPE,
                    'aud' => self::TOKEN_URI,
                    'iat' => $now,
                    'exp' => $now + 3600,
                ], JSON_THROW_ON_ERROR));

            if (! openssl_sign($assertion, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new FirebaseAuthenticationException('Unable to sign Firebase OAuth assertion.');
            }

            $response = Http::asForm()->timeout($this->timeout())->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion . '.' . $this->base64UrlEncode($signature),
            ]);

            if ($response->failed()) {
                Log::error('Firebase OAuth token request failed.', ['status' => $response->status(), 'body' => $response->json()]);
                throw new FirebaseAuthenticationException('Firebase authentication failed.');
            }

            return (string) $response->json('access_token');
        });
    }

    /**
     * Send a message to one Firebase registration token.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sendToToken(string $token, array $payload): array
    {
        try {
            $response = $this->http()->post($this->endpoint(), ['message' => $this->messagePayload($token, $payload)]);

            if ($response->failed()) {
                $status = $this->firebaseStatus($response->json());

                if (in_array($status, ['UNREGISTERED', 'INVALID_ARGUMENT', 'NOT_FOUND'], true)) {
                    $this->deviceTokenService->deactivateTokens([$token]);
                }

                Log::error('Firebase message failed.', ['status' => $response->status(), 'firebase_status' => $status]);
                throw new FirebaseMessagingException('Firebase message failed.', $status);
            }

            return $response->json() ?? [];
        } catch (FirebaseMessagingException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('Firebase message exception.', ['message' => $exception->getMessage()]);
            throw new FirebaseMessagingException('Firebase message failed.', null, 0, $exception);
        }
    }

    /**
     * Send the same payload to multiple Firebase registration tokens.
     *
     * @param array<int, string> $tokens
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sendToTokens(array $tokens, array $payload): array
    {
        $results = ['sent' => 0, 'failed' => 0, 'responses' => []];

        foreach (array_values(array_unique($tokens)) as $token) {
            try {
                $results['responses'][$token] = $this->sendToToken($token, $payload);
                $results['sent']++;
            } catch (FirebaseMessagingException $exception) {
                $results['failed']++;
                $results['responses'][$token] = ['error' => $exception->firebaseStatus() ?? $exception->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Send a data-only Firebase message.
     *
     * @param array<string, string|int|float|bool|null> $data
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sendData(string $token, array $data, array $options = []): array
    {
        return $this->sendToToken($token, array_merge($options, ['data' => $data]));
    }

    /**
     * Send a notification Firebase message.
     *
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function sendNotification(string $token, string $title, string $body, array $options = []): array
    {
        return $this->sendToToken($token, array_merge($options, ['title' => $title, 'body' => $body]));
    }

    /**
     * @return array{client_email:string, private_key:string, project_id?:string}
     */
    private function credentials(): array
    {
        $path = config('firebase.credentials');

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            throw new FirebaseConfigurationException('Firebase credentials file is not configured.');
        }

        $credentials = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new FirebaseConfigurationException('Firebase credentials file is invalid.');
        }

        return $credentials;
    }

    private function projectId(): string
    {
        $projectId = config('firebase.project_id');

        if (! is_string($projectId) || $projectId === '') {
            throw new FirebaseConfigurationException('Firebase project id is not configured.');
        }

        return $projectId;
    }

    private function timeout(): int
    {
        return max(1, (int) config('firebase.timeout', 10));
    }

    private function endpoint(): string
    {
        return sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $this->projectId());
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->accessToken())->timeout($this->timeout());
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function messagePayload(string $token, array $payload): array
    {
        $message = ['token' => $token];

        if (isset($payload['title']) || isset($payload['body']) || isset($payload['image'])) {
            $message['notification'] = array_filter([
                'title' => $payload['title'] ?? null,
                'body' => $payload['body'] ?? null,
                'image' => $payload['image'] ?? null,
            ], static fn ($value): bool => $value !== null);
        }

        if (! empty($payload['data']) && is_array($payload['data'])) {
            $message['data'] = collect($payload['data'])->map(fn ($value): string => (string) $value)->all();
        }

        $android = array_filter([
            'priority' => isset($payload['priority']) ? strtoupper((string) $payload['priority']) : null,
            'ttl' => isset($payload['ttl']) ? (string) $payload['ttl'] : null,
            'notification' => array_filter([
                'icon' => $payload['icon'] ?? null,
                'click_action' => $payload['click_action'] ?? null,
            ], static fn ($value): bool => $value !== null),
        ], static fn ($value): bool => $value !== null && $value !== []);

        if ($android !== []) {
            $message['android'] = $android;
        }

        return $message;
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function firebaseStatus(?array $body): ?string
    {
        return Arr::get($body, 'error.status');
    }

    private function cacheKey(): string
    {
        return 'firebase.access_token.' . $this->projectId();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}