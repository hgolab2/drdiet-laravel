<?php

namespace Tests\Unit;

use App\Models\DeviceToken;
use App\Services\Firebase\DeviceTokenService;
use App\Services\Firebase\FirebaseService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FirebaseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('diet_users')) {
            Schema::create('diet_users', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }
    }

    public function test_access_token_is_generated_and_cached(): void
    {
        Cache::flush();
        config([
            'firebase.project_id' => 'project-id',
            'firebase.credentials' => $this->fakeCredentials(),
            'firebase.timeout' => 5,
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'oauth-token', 'expires_in' => 3600]),
        ]);

        $service = new FirebaseService(app(DeviceTokenService::class));

        $this->assertSame('oauth-token', $service->accessToken());
        $this->assertSame('oauth-token', $service->accessToken());

        Http::assertSentCount(1);
    }

    public function test_invalid_firebase_token_is_deactivated(): void
    {
        Cache::flush();
        config([
            'firebase.project_id' => 'project-id',
            'firebase.credentials' => $this->fakeCredentials(),
            'firebase.timeout' => 5,
        ]);

        DeviceToken::query()->create([
            'token' => 'bad-token',
            'platform' => 'web',
            'is_active' => true,
        ]);

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'oauth-token', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'UNREGISTERED']], 404),
        ]);

        $service = new FirebaseService(app(DeviceTokenService::class));
        $service->sendToTokens(['bad-token'], ['title' => 'Hello', 'body' => 'World']);

        $this->assertDatabaseHas('device_tokens', [
            'token' => 'bad-token',
            'is_active' => false,
        ]);
    }

    private function fakeCredentials(): string
    {
        $privateKey = openssl_pkey_new([
            'config' => 'C:\\wamp64\\bin\\php\\php8.2.0\\extras\\ssl\\openssl.cnf',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($privateKey, $pem, null, [
            'config' => 'C:\\wamp64\\bin\\php\\php8.2.0\\extras\\ssl\\openssl.cnf',
        ]);

        $path = storage_path('framework/testing-firebase.json');
        file_put_contents($path, json_encode([
            'client_email' => 'firebase@example.test',
            'private_key' => $pem,
        ], JSON_THROW_ON_ERROR));

        return $path;
    }
}