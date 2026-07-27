<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DeviceTokenApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDietUsersTableForTest();
    }

    public function test_authenticated_user_can_store_device_token(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user);

        $this->postJson('/api/device-token', [
            'token' => 'fcm-token',
            'platform' => 'web',
            'browser' => 'Chrome',
            'device_name' => 'Workstation',
        ])->assertOk()->assertJsonPath('data.token', 'fcm-token');

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token',
            'is_active' => true,
        ]);
    }

    public function test_store_updates_existing_token(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        DeviceToken::query()->create([
            'user_id' => $otherUser->id,
            'token' => 'fcm-token',
            'platform' => 'ios',
            'is_active' => false,
        ]);

        Passport::actingAs($user);

        $this->postJson('/api/device-token', [
            'token' => 'fcm-token',
            'platform' => 'android',
            'browser' => null,
            'device_name' => 'Pixel',
        ])->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $user->id,
            'token' => 'fcm-token',
            'platform' => 'android',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_deactivate_device_token(): void
    {
        $user = User::factory()->create();

        DeviceToken::query()->create([
            'user_id' => $user->id,
            'token' => 'fcm-token',
            'platform' => 'web',
            'is_active' => true,
        ]);

        Passport::actingAs($user);

        $this->deleteJson('/api/device-token', ['token' => 'fcm-token'])
            ->assertOk()
            ->assertJsonPath('message', 'Device token deactivated.');

        $this->assertDatabaseHas('device_tokens', [
            'token' => 'fcm-token',
            'is_active' => false,
        ]);
    }

    private function createDietUsersTableForTest(): void
    {
        if (Schema::hasTable('diet_users')) {
            return;
        }

        Schema::create('diet_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
}